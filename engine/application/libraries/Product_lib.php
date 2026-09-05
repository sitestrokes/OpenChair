<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\api\DefaultApi;
use Amazon\ProductAdvertisingAPI\v1\ApiException;
use Amazon\ProductAdvertisingAPI\v1\Configuration;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\SearchItemsResource;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\PartnerType;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\ProductAdvertisingAPIClientException;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetBrowseNodesRequest;
use Amazon\ProductAdvertisingAPI\v1\com\amazon\paapi5\v1\GetBrowseNodesResource;
require_once('./application/libraries/vendor/autoload.php');

class Product_lib
{
	public $CI;
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('app_lib');
		
	}

	
	public function fetchAmazon($api_keys,$keyword,$category,$page_id = 1)
	{
		// echo $page_id;exit;
		// $page_id = trim($page_id);
		// print_r($api_keys);exit;
		$config = new Configuration();


		$access_key = $api_keys['access_key'];
		$secret_key = $api_keys['secret_key'];

		/*
	     * Add your credentials
	     * Please add your access key here
	     */
		$config->setAccessKey($access_key);
		    # Please add your secret key here
		$config->setSecretKey($secret_key);

		 # Please add your partner tag (store/tracking id) here
    	$partnerTag =  $api_keys['tag_id'];

	    /*
	     * PAAPI host and region to which you want to send request
	     * For more details refer: https://webservices.amazon.com/paapi5/documentation/common-request-parameters.html#host-and-region
	     */
	    $country = $api_keys['amazon_country'];
	    $config->setHost('webservices.amazon.'.$country.'');
	    $config->setRegion('us-east-1');

    	$apiInstance = new DefaultApi(
	    /*
	     * If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
	     * This is optional, `GuzzleHttp\Client` will be used as default.
	     */
        new GuzzleHttp\Client(), $config);

	    # Request initialization

	    # Specify keywords
	    // $keyword = $keyword;

	    /*
	     * Specify the category in which search request is to be made
	     * For more details, refer: https://webservices.amazon.com/paapi5/documentation/use-cases/organization-of-items-on-amazon/search-index.html
	     */
    	$searchIndex = $category ? $category : 'All';

	     $resources = array(
	        // GetBrowseNodesResource::CHILDREN,
	        SearchItemsResource::ITEM_INFOTITLE,
	        SearchItemsResource::OFFERSLISTINGSPRICE,
	        SearchItemsResource::IMAGESPRIMARYLARGE,
	        SearchItemsResource::ITEM_INFOFEATURES,
	        SearchItemsResource::BROWSE_NODE_INFOBROWSE_NODES,
	        // SearchItemsResource::BROWSE_NODE_INFOBROWSE_NODESANCESTOR,
	        SearchItemsResource::BROWSE_NODE_INFOBROWSE_NODESSALES_RANK,
	        SearchItemsResource::BROWSE_NODE_INFOWEBSITE_SALES_RANK


	    );


      # Forming the request
	    $searchItemsRequest = new SearchItemsRequest();
	    // print_r($searchItemsRequest);exit();
	    $searchItemsRequest->setSearchIndex($searchIndex);
	    $searchItemsRequest->setKeywords($keyword);
	    $searchItemsRequest->setItemCount(10);
	    $searchItemsRequest->setPartnerTag($partnerTag);
	    $searchItemsRequest->setPartnerType(PartnerType::ASSOCIATES);
	    $searchItemsRequest->setResources($resources);
	    $searchItemsRequest->setItemPage($page_id);

	    # Validating request
	    $invalidPropertyList = $searchItemsRequest->listInvalidProperties();
	    $length = count($invalidPropertyList);


	    // print_r($searchItemsRequest);exit;

	    if ($length > 0) {
	        $a['error_subject'] = "Error forming the request";
	        $errors = array();
	        foreach ($invalidPropertyList as $invalidProperty) {
	            array_push($errors, $invalidProperty);
	        }

	        $a['errors'] = $errors;

	        return $a;
	    }

	   	 # Sending the request

	    try {
	    	// echo "string";exit;
	        $searchItemsResponse = $apiInstance->searchItems($searchItemsRequest);

	        // echo 'API called successfully', PHP_EOL;exit;
	        // echo 'Complete Response: ', $searchItemsResponse, PHP_EOL;
	        // print_r($searchItemsResponse->getSearchResult());exit;
	        # Parsing the response
	        if ($searchItemsResponse->getSearchResult() != null) 
	        {
	            // echo 'Printing first item information in SearchResult:', PHP_EOL;
	            $item = $searchItemsResponse->getSearchResult()->getItems();

	            // print_r($item);exit();
	            if ($item != null) {

	            	return $item;
	               
	            }
	        }
	        if ($searchItemsResponse->getErrors() != null) {
	            
	            $error['Error code'] =  $searchItemsResponse->getErrors()[0]->getCode();;
	            $error['Error message'] =  $searchItemsResponse->getErrors()[0]->getMessage();
	        }
	    }catch (ApiException $exception) {
	        // echo "Error calling PA-API 5.0!", PHP_EOL;
	        $error['error_code'] = "HTTP Status Code: ";
	        $error['Error_Message'] = $exception->getMessage();
	        if ($exception->getResponseObject() instanceof ProductAdvertisingAPIClientException) {
	            $errors = $exception->getResponseObject()->getErrors();
	            // foreach ($errors as $error) {
	            //     echo "Error Type: ", $error->getCode(), PHP_EOL;
	            //     echo "Error Message: ", $error->getMessage(), PHP_EOL;
	            // }
	        } else {
	            $error["error_body"] =  $exception->getResponseBody();
	        }
	    } catch (Exception $exception) {
	        echo "Error Message: ", $exception->getMessage(), PHP_EOL;
	    }

	}


	public function fetchEbay($keyword , $categoryId ,$page_no ,$price_min  ,$price_max ,$feedback_min , $feedback_max ,$available_to ,$shipment_min_price,$shipment_price_max,$site,$condition,$api_keys)
    {
		$endpoint = 'https://svcs.ebay.com/services/search/FindingService/v1';  // URL to call
		$appid = $api_keys['ebayappid']; 
		$site = $api_keys['country'];
		$responseEncoding = 'XML';   // Format of the response
	    $apicall = "$endpoint?OPERATION-NAME=findItemsAdvanced"
	                    . "&SERVICE-VERSION=1.12.0"
	                    . "&GLOBAL-ID=$site"
	                    . "&SECURITY-APPNAME=" . $appid
	                    . "&RESPONSE-DATA-FORMAT=$responseEncoding"
	                    . "&keywords=$keyword"
	                    . "&paginationInput.entriesPerPage=10"
	                    . "&paginationInput.pageNumber=$page_no"
	                    . "&sortOrder=BestMatch"
	                    . "&outputSelector=SellerInfo"
	                    . ($categoryId ? "&categoryId=" . $categoryId : "")
	                    . "&itemFilter(0).name=ListingType&itemFilter(0).value=FixedPrice";	
		
		
		$xml = $this->CI->app_lib->processURL($apicall);
		

		if(empty($xml))
		{
			$xml = file_get_contents($apicall);

		}
		
	   	$presult = simplexml_load_string($xml);
        $request = json_decode(json_encode($presult), True);
		
		return $request;
														
	
	}


	public function get_rotationid( $globalid ) {
		if ( empty($globalid) ) return false;
		$list = array(
			'AT' => '5221-53469-19255-0',
			'AU' => '705-53470-19255-0',
			'FRBE' => '1553-53471-19255-0',
			'ENCA' => '706-53473-19255-0',
			'CH' => '5222-53480-19255-0',
			'DE' => '707-53477-19255-0',
			'ES' => '1185-53479-19255-0',
			'FR' => '709-53476-19255-0',
			'IE' => '5282-53468-19255-0',
			'IN' => '4686-53472-19255-0',
			'IT' => '724-53478-19255-0',
			'NL' => '1346-53482-19255-0',
			'GB' => '710-53481-19255-0',
			'US' => '711-53200-19255-0'
		);
			
		$globalid = str_replace('EBAY-', '', $globalid);
		return isset($list["$globalid"]) ? $list["$globalid"] : false;
	}


	public function generateClickbankUrl($api_key,$keyword,$rules,$max_result)
	{

		$url = "https://accounts.clickbank.com/mkplSearchResult.htm?resultsPerPage=".$max_result;

		$url .= '&includeKeywords='.urlencode($keyword);


		$item_language   = 'All';
		if(!empty($rules))
		{
		
			// $url .= '&'.htmlspecialchars_decode($rules['subcategory_name']);
			$item_language   = $rules['language'];

			$item_gravity = $rules['item_gravity'];
			$dollar_sale = $rules['item_intial_d_sale'];
			$dollar_sale_av = $rules['item_avg_d_sale'];
			$per_sale = $rules['item_avg_per_sale'];
			$rebill_total = $rules['item_avg_rebill'];
			$per_rebill = $rules['item_per_rebill'];

			$sort_results = $rules['sort_by'];

			if($item_gravity != '')
			{
			    if(strstr($item_gravity, '-'))
			    {
			        $gravities = explode('-', $item_gravity);
			        if(count($gravities) == 2 && is_numeric(trim($gravities[0])) && is_numeric(trim($gravities[1])))
			        {
			            $url .= '&gravityEnabled=true&_gravityEnabled=on&gravityType=BETWEEN&gravityV1=' . trim($gravities[0]) . '&gravityV2=' . trim($gravities[1]);
			        }
			    }
			    elseif(strstr($item_gravity, '<'))
			    {
			        $item_gravity = trim($item_gravity);
			        $item_gravity = trim($item_gravity, '<');
			        if(is_numeric($item_gravity))
			        {
			            $url .= '&gravityEnabled=true&_gravityEnabled=on&gravityType=LOWER&gravityV1=' . trim($item_gravity) . '&gravityV2=';
			        }
			    }
			    elseif(strstr($item_gravity, '>'))
			    {
			        $item_gravity = trim($item_gravity);
			        $item_gravity = trim($item_gravity, '>');
			        if(is_numeric($item_gravity))
			        {
			            $url .= '&gravityEnabled=true&_gravityEnabled=on&gravityType=HIGHER&gravityV1=' . trim($item_gravity) . '&gravityV2=';
			        }
			    }
			}


			if($dollar_sale != '')
			{
			    if(strstr($dollar_sale, '-'))
			    {
			        $gravities = explode('-', $dollar_sale);
			        if(count($gravities) == 2 && is_numeric(trim($gravities[0])) && is_numeric(trim($gravities[1])))
			        {
			            $url .= '&initialEarningsPerSaleEnabled=true&_initialEarningsPerSaleEnabled=on&initialEarningsPerSaleType=BETWEEN&initialEarningsPerSaleV1=' . trim($gravities[0]) . '&initialEarningsPerSaleV2=' . trim($gravities[1]);
			        }
			    }
			    elseif(strstr($dollar_sale, '<'))
			    {
			        $dollar_sale = trim($dollar_sale);
			        $dollar_sale = trim($dollar_sale, '<');
			        if(is_numeric($dollar_sale))
			        {
			            $url .= '&initialEarningsPerSaleEnabled=true&_initialEarningsPerSaleEnabled=on&initialEarningsPerSaleType=LOWER&initialEarningsPerSaleV1=' . trim($dollar_sale) . '&initialEarningsPerSaleV2=';
			        }
			    }
			    elseif(strstr($dollar_sale, '>'))
			    {
			        $dollar_sale = trim($dollar_sale);
			        $dollar_sale = trim($dollar_sale, '>');
			        if(is_numeric($dollar_sale))
			        {
			            $url .= '&initialEarningsPerSaleEnabled=true&_initialEarningsPerSaleEnabled=on&initialEarningsPerSaleType=HIGHER&initialEarningsPerSaleV1=' . trim($dollar_sale) . '&initialEarningsPerSaleV2=';
			        }
			    }
			}


			if($dollar_sale_av != '')
			{
			    if(strstr($dollar_sale_av, '-'))
			    {
			        $gravities = explode('-', $dollar_sale_av);
			        if(count($gravities) == 2 && is_numeric(trim($gravities[0])) && is_numeric(trim($gravities[1])))
			        {
			            $url .= '&averageEarningsPerSaleEnabled=true&_averageEarningsPerSaleEnabled=on&averageEarningsPerSaleType=BETWEEN&averageEarningsPerSaleV1=' . trim($gravities[0]) . '&averageEarningsPerSaleV2=' . trim($gravities[1]);
			        }
			    }
			    elseif(strstr($dollar_sale_av, '<'))
			    {
			        $dollar_sale_av = trim($dollar_sale_av);
			        $dollar_sale_av = trim($dollar_sale_av, '<');
			        if(is_numeric($dollar_sale_av))
			        {
			            $url .= '&averageEarningsPerSaleEnabled=true&_averageEarningsPerSaleEnabled=on&averageEarningsPerSaleType=LOWER&averageEarningsPerSaleV1=' . trim($dollar_sale_av) . '&initialEarningsPerSaleV2=';
			        }
			    }
			    elseif(strstr($dollar_sale_av, '>'))
			    {
			        $dollar_sale_av = trim($dollar_sale_av);
			        $dollar_sale_av = trim($dollar_sale_av, '>');
			        if(is_numeric($dollar_sale_av))
			        {
			            $url .= '&averageEarningsPerSaleEnabled=true&_averageEarningsPerSaleEnabled=on&averageEarningsPerSaleType=HIGHER&averageEarningsPerSaleV1=' . trim($dollar_sale_av) . '&initialEarningsPerSaleV2=';
			        }
			    }
			}


			if($per_sale != '')
			{
			    if(strstr($per_sale, '-'))
			    {
			        $gravities = explode('-', $per_sale);
			        if(count($gravities) == 2 && is_numeric(trim($gravities[0])) && is_numeric(trim($gravities[1])))
			        {
			            $url .= '&percentPerSaleEnabled=true&_percentPerSaleEnabled=on&percentPerSaleType=BETWEEN&percentPerSaleV1=' . trim($gravities[0]) . '&percentPerSaleV2=' . trim($gravities[1]);
			        }
			    }
			    elseif(strstr($per_sale, '<'))
			    {
			        $per_sale = trim($per_sale);
			        $per_sale = trim($per_sale, '<');
			        if(is_numeric($per_sale))
			        {
			            $url .= '&percentPerSaleEnabled=true&_percentPerSaleEnabled=on&percentPerSaleType=LOWER&percentPerSaleV1=' . trim($per_sale) . '&percentPerSaleV2=';
			        }
			    }
			    elseif(strstr($per_sale, '>'))
			    {
			        $per_sale = trim($per_sale);
			        $per_sale = trim($per_sale, '>');
			        if(is_numeric($per_sale))
			        {
			            $url .= '&percentPerSaleEnabled=true&_percentPerSaleEnabled=on&percentPerSaleType=HIGHER&percentPerSaleV1=' . trim($per_sale) . '&percentPerSaleV2=';
			        }
			    }
			}


			if($rebill_total != '')
			{
			    if(strstr($rebill_total, '-'))
			    {
			        $gravities = explode('-', $rebill_total);
			        if(count($gravities) == 2 && is_numeric(trim($gravities[0])) && is_numeric(trim($gravities[1])))
			        {
			            $url .= '&futureEarningsEnabled=true&_futureEarningsEnabled=on&futureEarningsType=BETWEEN&futureEarningsV1=' . trim($gravities[0]) . '&futureEarningsV2=' . trim($gravities[1]);
			        }
			    }
			    elseif(strstr($rebill_total, '<'))
			    {
			        $rebill_total = trim($rebill_total);
			        $rebill_total = trim($rebill_total, '<');
			        if(is_numeric($rebill_total))
			        {
			            $url .= '&futureEarningsEnabled=true&_futureEarningsEnabled=on&futureEarningsType=LOWER&futureEarningsV1=' . trim($rebill_total) . '&initialEarningsPerSaleV2=';
			        }
			    }
			    elseif(strstr($rebill_total, '>'))
			    {
			        $rebill_total = trim($rebill_total);
			        $rebill_total = trim($rebill_total, '>');
			        if(is_numeric($rebill_total))
			        {
			            $url .= '&futureEarningsEnabled=true&_futureEarningsEnabled=on&futureEarningsType=HIGHER&futureEarningsV1=' . trim($rebill_total) . '&futureEarningsV2=';
			        }
			    }
			}

			if($per_rebill != '')
			{
			    if(strstr($per_rebill, '-'))
			    {
			        $gravities = explode('-', $per_rebill);
			        if(count($gravities) == 2 && is_numeric(trim($gravities[0])) && is_numeric(trim($gravities[1])))
			        {
			            $url .= '&percentPerRebillEnabled=true&_percentPerRebillEnabled=on&percentPerRebillType=BETWEEN&percentPerRebillV1=' . trim($gravities[0]) . '&percentPerRebillV2=' . trim($gravities[1]);
			        }
			    }
			    elseif(strstr($per_rebill, '<'))
			    {
			        $per_rebill = trim($per_rebill);
			        $per_rebill = trim($per_rebill, '<');
			        if(is_numeric($per_rebill))
			        {
			            $url .= '&percentPerRebillEnabled=true&_percentPerRebillEnabled=on&percentPerRebillType=LOWER&percentPerRebillV1=' . trim($per_rebill) . '&percentPerRebillV2=';
			        }
			    }
			    elseif(strstr($per_rebill, '>'))
			    {
			        $per_rebill = trim($per_rebill);
			        $per_rebill = trim($per_rebill, '>');
			        if(is_numeric($per_rebill))
			        {
			            $url .= '&percentPerRebillEnabled=true&_percentPerRebillEnabled=on&percentPerRebillType=HIGHER&percentPerRebillV1=' . trim($per_rebill) . '&percentPerRebillV2=';
			        }
			    }
			}

			if($item_language != '' && $item_language != 'All')
			{
			    if($item_language == 'German')
			    {
			        $url .= '&_productLanguages=on&productLanguages=DE';
			    }
			    elseif($item_language == 'English')
			    {
			        $url .= '&_productLanguages=on&productLanguages=EN';
			    }
			    elseif($item_language == 'Spanish')
			    {
			        $url .= '&_productLanguages=on&productLanguages=ES';
			    }
			    elseif($item_language == 'French')
			    {
			        $url .= '&_productLanguages=on&productLanguages=FR';
			    }
			    elseif($item_language == 'Portuguese')
			    {
			        $url .= '&_productLanguages=on&productLanguages=PT';
			    }
			    elseif($item_language == 'Italian')
			    {
			        $url .= '&_productLanguages=on&productLanguages=IT';
			    }
			}
			if($sort_results != 'relevance')
			{
			    $url .= '&sortField=' . $sort_results;
			}
		}

		return $url;
		
	}


	public function scrapeClickbank($url, $auth = '')
	{
	    $content = false;
	    $headersx = array();
	    if($auth != '')
	    {
	        $headersx = array('Accept' => 'application/xml', 'Authorization' => $auth);
	    }
	    $args = array(
	       'timeout'     => 10,
	       'redirection' => 10,
	       'user-agent'  => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
	       'blocking'    => true,
	       'headers'     => $headersx,
	       'cookies'     => array(),
	       'body'        => null,
	       'compress'    => false,
	       'decompress'  => true,
	       'sslverify'   => false,
	       'stream'      => false,
	       'filename'    => null
	    );
	    $ret_data            = $this->clicky_remote_get(html_entity_decode($url), $args,$auth);  


	    if(!empty($ret_data) && $ret_data['header']['http_code'] =='200')
	    {
	        $content = $ret_data['body'];
	    }

	    if($content === false)
	    {
	      
	        if(function_exists('curl_version') && filter_var($url, FILTER_VALIDATE_URL))
	        {
	            $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36';
	            $options    = array(
	                CURLOPT_CUSTOMREQUEST => "GET",
	                CURLOPT_POST => false,
	                CURLOPT_USERAGENT => $user_agent,
	                CURLOPT_RETURNTRANSFER => true,
	                CURLOPT_HEADER => false,
	                CURLOPT_FOLLOWLOCATION => true,
	                CURLOPT_ENCODING => "",
	                CURLOPT_AUTOREFERER => true,
	                CURLOPT_CONNECTTIMEOUT => 10,
	                CURLOPT_TIMEOUT => 10,
	                CURLOPT_MAXREDIRS => 10,
	                CURLOPT_SSL_VERIFYHOST => 0,
	                CURLOPT_SSL_VERIFYPEER => 0
	            );
	            if($auth != '')
	            {
	                $options[CURLOPT_HTTPHEADER] = array(
	                    'Accept: application/xml',
	                    'Authorization: ' . $auth,
	                );
	            }
	            $ch = curl_init($url);
	            if ($ch === FALSE) {
	                return FALSE;
	            }
	            curl_setopt_array($ch, $options);
	            $content = curl_exec($ch);
	            curl_close($ch);
	        }
	        else
	        {
	            return file_get_contents($url);
	        }
	    }
	    return $content;
	}



	private function clicky_remote_get($url,$args,$api_key)
	{
		$ch = curl_init();
	    // curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	  curl_setopt($ch, CURLOPT_HEADER, 1);
	  // curl_setopt($ch, CURLOPT_BODY, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_POSTREDIR, 10);
	    curl_setopt($ch, CURLOPT_HEADER, array('Accept' => 'application/xml', 'Authorization' => $api_key));
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36');
	    curl_setopt($ch, CURLOPT_URL, $url);
	    
	    $data = curl_exec($ch);
	    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	    $response1 = curl_getinfo($ch);

	    $header = substr($data, 0, $header_size);
	    $body = substr($data, $header_size);
	    // print_r($res);exit;
	    // $data1 = array($header,$body)


	    $a['header'] = $response1;
	    $a['body'] = $body;

	    // print_r($a);exit;
	    curl_close($ch);
	    // print_r($body);exit;
	    return $a;
	}
	


    
}


?>