<?php

namespace Alnv\ContaoElasticsearchLocherBundle\Controller;

use Contao\Input;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Alnv\ContaoElasticsearchLocherBundle\Helpers\Credentials;
use Alnv\ContaoElasticsearchLocherBundle\Adapter\Elasticsearch;

/**
 *
 * @Route("/ps", defaults={"_scope" = "frontend", "_token_check" = false})
 */
class ProSearchController extends \Contao\CoreBundle\Controller\AbstractController {

    /**
     *
     * @Route("/search/results", methods={"POST", "GET"}, name="get-search-results")
     */
    public function getSearchResults() {

        $this->container->get('contao.framework')->initialize();

        $arrJsonData = \json_decode(file_get_contents('php://input'), true);

        if (!empty($arrJsonData) && is_array($arrJsonData)) {
            \Input::setPost('root', $arrJsonData['root']);
            \Input::setPost('module', $arrJsonData['module']);
            \Input::setPost('categories', $arrJsonData['categories']);
        }

        $arrCategories = Input::post('categories') ?? [];
        $strModuleId = Input::post('module') ?: (Input::get('module') ?? '');
        $strRootPageId = Input::post('root') ?: (Input::get('root') ?? '');
        $strQuery = Input::get('query') ?? '';

        $objKeyword = new \Alnv\ContaoElasticsearchLocherBundle\Helpers\Keyword();
        $arrKeywords = $objKeyword->setKeywords($strQuery, ['categories' => $arrCategories]);

        $objCredentials = new Credentials();
        $arrCredentials = $objCredentials->getCredentials();

        $arrResults = [
            'keywords' => $arrKeywords,
            'results' => []
        ];

        switch ($arrCredentials['type']) {
            case 'elasticsearch':
            case 'elasticsearch_cloud':
                $objElasticsearchAdapter = new Elasticsearch($strModuleId, $strRootPageId);
                $objElasticsearchAdapter->connect();
                if ($objElasticsearchAdapter->getClient()) {
                    $arrResults['results'] = $objElasticsearchAdapter->search($arrKeywords);
                }
                break;
            case 'licence':
                // todo
                break;
        }

        $objModule = \ModuleModel::findByPk($strModuleId);
        $strSearchResultsTemplate = $objModule ? ($objModule->psResultsTemplate??'ps_search_result') : 'ps_search_result';

        foreach (($arrResults['results']['hits']??[]) as $index => $arrResult) {
            $objTemplate = new \FrontendTemplate($strSearchResultsTemplate);
            $objTemplate->setData($arrResult);
            $arrResults['results']['hits'][$index]['template'] = \Controller::replaceInsertTags($objTemplate->parse());
        }

        return new JsonResponse($arrResults);
    }

    /**
     *
     * @Route("/search/autocompletion", methods={"POST", "GET"}, name="get-search-autocompletion")
     */
    public function getAutoCompletion() {

        $this->container->get('contao.framework')->initialize();

        $arrJsonData = \json_decode(file_get_contents('php://input'), true);

        if (!empty($arrJsonData) && is_array($arrJsonData)) {
            \Input::setPost('root', $arrJsonData['root']);
            \Input::setPost('module', $arrJsonData['module']);
            \Input::setPost('categories', $arrJsonData['categories']);
        }

        $arrCategories = Input::post('categories') ?? [];
        $strModuleId = Input::post('module') ?: (Input::get('module') ?? '');
        $strRootPageId = Input::post('root') ?: (Input::get('root') ?? '');
        $query = Input::get('query') ?? '';

        $objCredentials = new Credentials();
        $arrCredentials = $objCredentials->getCredentials();

        $objKeyword = new \Alnv\ContaoElasticsearchLocherBundle\Helpers\Keyword();
        $arrKeywords = $objKeyword->setKeywords($query, ['categories' => $arrCategories]);

        $arrResults = [
            'keywords' => $arrKeywords,
            'results' => []
        ];

        switch ($arrCredentials['type']) {
            case 'elasticsearch':
            case 'elasticsearch_cloud':
                $objElasticsearchAdapter = new Elasticsearch($strModuleId, $strRootPageId);
                $objElasticsearchAdapter->connect();
                if ($objElasticsearchAdapter->getClient()) {
                    $arrResults['results'] = $objElasticsearchAdapter->autocompltion($arrKeywords);
                }
                break;
            case 'licence':
                // todo
                break;
        }

        return new JsonResponse($arrResults);
    }
}