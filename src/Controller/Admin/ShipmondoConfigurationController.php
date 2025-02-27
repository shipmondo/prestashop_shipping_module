<?php
/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

declare(strict_types=1);

namespace Shipmondo\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ShipmondoConfigurationController extends FrameworkBundleAdminController
{
    public const TAB_CLASS_NAME = 'AdminShipmondoConfiguration';

    public function indexAction(Request $request): Response
    {
        $textFormDataHandler = $this->get('shipmondo.form.shipmondo_configuration_form_handler');

        $textForm = $textFormDataHandler->getForm();
        $textForm->handleRequest($request);

        if ($textForm->isSubmitted() && $textForm->isValid()) {
            /** You can return array of errors in form handler and they can be displayed to user with flashErrors */
            $errors = $textFormDataHandler->save($textForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('shipmondo_configuration');
            }

            $this->flashErrors($errors);
        }

        return $this->render('@Modules/shipmondo/views/templates/admin/shipmondo_configuration_form.html.twig', [
            'enableSidebar' => true,
            'layoutTitle' => $this->trans('Shipmondo Delivery Checkout', 'Modules.Shipmondo.Admin'),
            'shipmondoConfigurationForm' => $textForm->createView(),
        ]);
    }
}
