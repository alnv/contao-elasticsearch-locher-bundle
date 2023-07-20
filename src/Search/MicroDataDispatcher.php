<?php

namespace Alnv\ContaoElasticsearchLocherBundle\Search;

use Alnv\ContaoElasticsearchLocherBundle\MicroData\Article;
use Alnv\ContaoElasticsearchLocherBundle\MicroData\Dataset;
use Alnv\ContaoElasticsearchLocherBundle\MicroData\Event;
use Alnv\ContaoElasticsearchLocherBundle\MicroData\FAQPage;
use Alnv\ContaoElasticsearchLocherBundle\MicroData\JobPosting;
use Alnv\ContaoElasticsearchLocherBundle\MicroData\Person;
use Alnv\ContaoElasticsearchLocherBundle\MicroData\Product;
use Contao\CoreBundle\Search\Document;

/**
 *
 */
class MicroDataDispatcher
{

    /**
     * @param Document $document
     * @param int $indicesId
     */
    public function __construct(Document $document, int $indicesId)
    {
        (new FAQPage())->dispatch($document->extractJsonLdScripts('https://schema.org', 'FAQPage'), $indicesId);
        (new Article())->dispatch($document->extractJsonLdScripts('https://schema.org', 'Article'), $indicesId);
        (new Person())->dispatch($document->extractJsonLdScripts('https://schema.org', 'Person'), $indicesId);
        (new Event())->dispatch($document->extractJsonLdScripts('https://schema.org', 'Event'), $indicesId);
        (new Dataset())->dispatch($document->extractJsonLdScripts('https://schema.org', 'Dataset'), $indicesId);
        (new JobPosting())->dispatch($document->extractJsonLdScripts('https://schema.org', 'JobPosting'), $indicesId);
        (new Product())->dispatch($document->extractJsonLdScripts('https://schema.org', 'Product'), $indicesId);
    }
}