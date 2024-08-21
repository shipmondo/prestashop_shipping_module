<?php

namespace Shipmondo\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Shipmondo\Entity\ShipmondoCarrier;
use Doctrine\ORM\EntityManagerInterface;

class ShipmondoCarrierController extends FrameworkBundleAdminController
{
    const TAB_CLASS_NAME = 'AdminShipmondoShipmondoCarrier';

    public function indexAction()
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $repo = $entityManager->getRepository(ShipmondoCarrier::class);
        $carriers = $repo->findAll();

        return $this->render('@Modules/shipmondo/views/templates/admin/index.html.twig', [
            'carriers' => $carriers,
        ]);
    }
}