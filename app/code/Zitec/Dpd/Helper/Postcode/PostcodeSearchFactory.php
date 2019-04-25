<?php

namespace Zitec\Dpd\Helper\Postcode;


use Magento\Framework\App\ResourceConnection;

class PostcodeSearchFactory
{
    /** @var ResourceConnection */
    private $connection;

    /**
     * PostcodeSearchFactory constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    public function create($searchModel, $connection = null)
    {
        if (is_null($connection)) {
            $connection = $this->connection;
        }

        return new \Zitec_Dpd_Postcode_Search($searchModel, $connection);
    }
}
