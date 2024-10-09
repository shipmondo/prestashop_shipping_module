<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Controller\Admin;

use Carrier;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Service\Grid\ResponseBuilder;
use Shipmondo\Entity\ShipmondoCarrier;
use Shipmondo\Form\Type\ShipmondoCarrierFormType;
use Shipmondo\Grid\Definition\Factory\ShipmondoCarrierGridDefinitionFactory;
use Shipmondo\Grid\Filters\ShipmondoCarrierFilters;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ShipmondoCarrierController extends FrameworkBundleAdminController
{
    public const TAB_CLASS_NAME = 'AdminShipmondoShipmondoCarrier';

    public function indexAction(ShipmondoCarrierFilters $filters): Response
    {
        $carrierGridFactory = $this->get('shipmondo.grid.factory.shipmondo_carriers');
        $carrierGrid = $carrierGridFactory->getGrid($filters);

        return $this->render(
            '@Modules/shipmondo/views/templates/admin/shipmondo_carrier_index.html.twig',
            [
                'enableSidebar' => true,
                'layoutTitle' => $this->trans('Shipmondo Carriers', 'Modules.Shipmondo.Admin'),
                'layoutHeaderToolbarBtn' => [
                    'add' => [
                        'desc' => $this->trans('Add Shipmondo Carrier', 'Modules.Shipmondo.Admin'),
                        'icon' => 'add_circle_outline',
                        'href' => $this->generateUrl('shipmondo_shipmondo_carriers_create'),
                    ],
                ],
                'carrierGrid' => $this->presentGrid($carrierGrid),
            ]
        );
    }

    public function searchAction(Request $request): Response
    {
        /** @var ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('prestashop.bundle.grid.response_builder');

        return $responseBuilder->buildSearchResponse(
            $this->get('shipmondo.grid.definition.factory.shipmondo_carriers'),
            $request,
            ShipmondoCarrierGridDefinitionFactory::GRID_ID,
            'shipmondo_shipmondo_carriers_index'
        );
    }

    public function createAction(Request $request): Response
    {
        $carrier = new ShipmondoCarrier();
        $form = $this->createForm(ShipmondoCarrierFormType::class, $carrier, ['action' => $this->generateUrl('shipmondo_shipmondo_carriers_create')]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($carrier->getCarrierId() == 0) {
                $this->createPsCarrier($carrier);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($carrier);
            $em->flush();

            return $this->redirectToRoute('shipmondo_shipmondo_carriers_index');
        }

        return $this->render('@Modules/shipmondo/views/templates/admin/shipmondo_carrier_form.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->trans('Shipmondo Carrier', 'Modules.Shipmondo.Admin'),
            'isEdit' => false,
        ]);
    }

    public function editAction(Request $request, int $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $carrier = $em->getRepository(ShipmondoCarrier::class)->find($id);

        if (!$carrier) {
            throw $this->createNotFoundException('The carrier does not exist');
        }

        $form = $this->createForm(ShipmondoCarrierFormType::class, $carrier, ['action' => $this->generateUrl('shipmondo_shipmondo_carriers_edit', ['id' => $id])]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('shipmondo_shipmondo_carriers_index');
        }

        return $this->render('@Modules/shipmondo/views/templates/admin/shipmondo_carrier_form.html.twig', [
            'form' => $form->createView(),
            'layoutTitle' => $this->trans('Shipmondo Carrier', 'Modules.Shipmondo.Admin'),
            'isEdit' => true,
        ]);
    }

    public function deleteAction(int $id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $carrier = $em->getRepository(ShipmondoCarrier::class)->find($id);

        if (!$carrier) {
            throw $this->createNotFoundException('The carrier does not exist');
        }

        $em->remove($carrier);
        $em->flush();

        return $this->redirectToRoute('shipmondo_shipmondo_carriers_index');
    }

    /**
     * Create a new carrier in PrestaShop and set relation to Shipmondo Carrier
     *
     * @param ShipmondoCarrier $carrier
     */
    private function createPsCarrier(ShipmondoCarrier $carrier): void
    {
        $carrierHandler = $this->get('shipmondo.carrier_handler');
        $carrierName = $carrierHandler->getCarrierName($carrier->getCarrierCode());
        $productName = $carrierHandler->getProductName($carrier->getProductCode());

        $psCarrier = new \Carrier();
        $psCarrier->name = $carrierName . ' - ' . $productName;
        $psCarrier->active = false;
        $psCarrier->deleted = false;
        $psCarrier->shipping_handling = true;
        $psCarrier->range_behavior = 0;
        $psCarrier->is_module = true;
        $psCarrier->shipping_external = true;
        $psCarrier->external_module_name = 'shipmondo';
        $psCarrier->need_range = true;
        $psCarrier->is_free = true;
        $psCarrier->delay[\Configuration::get('PS_LANG_DEFAULT')] = ' ';

        if ($psCarrier->add()) {
            $groups = \Group::getGroups(true);
            $group_ids = array_column($groups, 'id_group');
            $psCarrier->setGroups($group_ids);

            $zones = \Zone::getZones(true);
            foreach ($zones as $zone) {
                $psCarrier->addZone($zone['id_zone']);
            }

            $carrier->setCarrierId($psCarrier->id);
        }
    }
}
