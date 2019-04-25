<?php

namespace Zitec\Dpd\Controller\Adminhtml\Shipment;


use Magento\Backend\App\Action\Context;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Manifest extends AbstractMassAction
{
    /**
     * @var \Zitec\Dpd\Model\Dpd\ManifestFactory
     */
    private $dpdDpdManifestFactory;
    /**
     * @var \Zitec\Dpd\Helper\Data
     */
    private $dpdHelper;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Zitec\Dpd\Model\Dpd\ManifestFactory $dpdDpdManifestFactory,
        \Zitec\Dpd\Helper\Data $dpdHelper
    ) {
        parent::__construct($context, $filter);

        $this->dpdDpdManifestFactory = $dpdDpdManifestFactory;
        $this->dpdHelper = $dpdHelper;
        $this->collectionFactory = $collectionFactory;
    }

    public function massAction(AbstractCollection $collection)
    {
        $shipments = $collection->getItems();
        $shipmentIds = [];
        foreach ($shipments as $shipment) {
            $shipmentIds[] = $shipment->getId();
        }

        $manifest = $this->dpdDpdManifestFactory->create();
        /* @var $manifest \Zitec\Dpd\Model\Dpd\Manifest */
        try {
            $success       = $manifest->createManifestForShipments($shipmentIds);
            $notifications = $manifest->getNotifications();
            if ($success) {
                $downloadLinkMessage = "Successfully closed manifest %1 for the following shipments. <a href='%2'>Download the manifest</a>.";
                array_unshift($notifications, __($downloadLinkMessage, $manifest->getManifestRef(), $this->dpdHelper->getDownloadManifestUrl($manifest->getManifestId())));
            }
            $message = implode("<br />", $notifications);
            $this->dpdHelper->addSuccessError($success, $message);
        } catch (\Exception $e) {
            $this->dpdHelper->addError(__("An error occurred whilst closing the manifest: %1", $e->getMessage()));
            $this->dpdHelper->log($e->getMessage(), __FUNCTION__, __CLASS__, __LINE__);
        }

        $this->_redirect('sales/shipment');
    }
}
