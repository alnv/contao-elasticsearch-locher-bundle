<?php

namespace Alnv\ContaoElasticsearchLocherBundle\Cron;

use Alnv\ContaoElasticsearchLocherBundle\Helpers\States;
use Alnv\ContaoElasticsearchLocherBundle\Adapter\Elasticsearch;

class DeleteIndices
{
    public function __invoke()
    {
        $objElasticsearch = new Elasticsearch();
        $objIndices = \Database::getInstance()->prepare('SELECT * FROM tl_indices WHERE state=?')->limit(50)->execute(States::DELETE);

        while ($objIndices->next()) {

            $objElasticsearch->deleteIndex($objIndices->id);
        }
    }
}