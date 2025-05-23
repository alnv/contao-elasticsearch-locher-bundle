<?php

namespace Alnv\ContaoElasticsearchLocherBundle\Search;

use Alnv\ContaoElasticsearchLocherBundle\Helpers\States;
use Alnv\ContaoElasticsearchLocherBundle\Models\IndicesModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;

/**
 * https://docs.contao.org/dev/framework/search-indexing/
 */
class ProSearchIndexer implements IndexerInterface
{

    private ContaoFramework $framework;

    private bool $indexProtected;

    public function __construct(ContaoFramework $framework, bool $indexProtected = false)
    {
        $this->framework = $framework;
        $this->indexProtected = $indexProtected;
    }

    public function index(Document $document): void
    {

        if (200 !== $document->getStatusCode()) {
            $this->throwBecause('HTTP Statuscode is not equal to 200.');
        }

        if ('' === $document->getBody()) {
            $this->throwBecause('Cannot index empty response.');
        }

        try {
            $title = $document->getContentCrawler()->filterXPath('//head/title')->first()->text(null, true);
        } catch (\Exception $e) {
            $title = '';
        }

        try {
            $language = $document->getContentCrawler()->filterXPath('//html[@lang]')->first()->attr('lang');
        } catch (\Exception $e) {
            $language = 'en';
        }

        $meta = [
            'title' => $title,
            'language' => $language,
            'protected' => false,
            'groups' => []
        ];

        $this->extendMetaFromJsonLdScripts($document, $meta);

        if (!isset($meta['pageId']) || 0 === $meta['pageId']) {
            $this->throwBecause('No page ID could be determined.');
        }

        // If search was disabled in the page settings, we do not index
        if (isset($meta['noSearch']) && true === $meta['noSearch']) {
            $this->throwBecause('Was explicitly marked "noSearch" in page settings.');
        }

        // If the front end preview is activated, we do not index
        if (isset($meta['fePreview']) && true === $meta['fePreview']) {
            $this->throwBecause('Indexing when the front end preview is enabled is not possible.');
        }

        $this->framework->initialize();

        new Indices($document, $meta);
        new PDFIndices($document, $meta);
    }

    public function delete(Document $document): void
    {

        $this->framework->initialize();

        $strUrl = $document->getUri()->__toString();
        $objIndices = IndicesModel::findByUrl($strUrl);

        if (!$objIndices) {
            return;
        }

        $objIndices->state = States::DELETE;
        $objIndices->save();
    }

    public function clear(): void
    {
        $this->framework->initialize();

        $objIndices = IndicesModel::findAll();

        if (!$objIndices) {
            return;
        }

        while ($objIndices->next()) {
            $objIndices->state = States::DELETE;
            $objIndices->save();
        }
    }

    private function throwBecause(string $message, bool $onlyWarning = true): void
    {
        if ($onlyWarning) {
            throw IndexerException::createAsWarning($message);
        }

        throw new IndexerException($message);
    }

    private function extendMetaFromJsonLdScripts(Document $document, array &$meta): void
    {
        $jsonLds = $document->extractJsonLdScripts('https://schema.contao.org/', 'Page');

        if (0 === \count($jsonLds)) {
            $jsonLds = $document->extractJsonLdScripts('https://schema.contao.org/', 'RegularPage');

            if (0 === \count($jsonLds)) {
                $this->throwBecause('No JSON-LD found.');
            }
        }

        $meta = array_merge($meta, array_merge(...$jsonLds));
    }
}