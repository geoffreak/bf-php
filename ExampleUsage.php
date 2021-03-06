<?php

$path_to_BillForward = 'lib/BillForward.php';
require_once($path_to_BillForward);

//namespace BFPHPClientTest;
//echo "Running Bf_Account tests for BillForward PHP Client Library.\n";

//use BFPHPClient\Account;



function getUsualAccountsProfileEmail() {
	return 'full@account.is.moe';
}

function getUsualPrpName() {
	return 'Cool Plan';
}


// Grab an API token from: https://app-sandbox.billforward.net/setup/#/personal/api-keys
$access_token = 'YOUR ACCESS TOKEN HERE';
$urlRoot = 'https://api-sandbox.billforward.net/2014.223.0/';
$client = new BfClient($access_token, $urlRoot);

//-- Find the account we login with (assume first found with associated user)
// order by userID so that we are likely to see our login user's account
$accounts = $client
->accounts
->getAll([
	'order_by' => 'userID'
	]);

$foundLoginAccount = NULL;
foreach ($accounts as $account) {
	if (array_key_exists('userID', $account)) {
		$foundLoginAccount = $account;
		break;
	}
}
if (is_null($foundLoginAccount)) {
	throw new Exception('Login account not found.');
}
//var_export($foundLoginAccount);

//-- Get the organization we log in with (assume first found)
$orgs = $client
->organisations
->getMine();

$firstOrg = $orgs[0];
$firstOrgID = $firstOrg->id;


// Create (upon our organisation) API configuration for Authorize.net
$AuthorizeNetLoginID = 'AUTHORIZE NET LOGIN ID HERE';
$AuthorizeNetTransactionKey = 'AUTHORIZE NET TRANSACTION KEY HERE';

// saving this twice to the same organisation seems to make a copy.
// so probably you sohuld clear out your `api_configurations` in SQL before running this a second time.
$apiConfiguration = new Bf_APIConfiguration($client, [
	 "@type" => "AuthorizeNetConfiguration",
     "APILoginID" => $AuthorizeNetLoginID,
     "transactionKey" => $AuthorizeNetTransactionKey,
     "environment" => "Sandbox"
	]);

$firstOrg
->apiConfigurations = [$apiConfiguration];

$savedOrg = $firstOrg
->save();

//-- Make account with expected profile
$email = getUsualAccountsProfileEmail();
$profile = new Bf_Profile($client, [
	'email' => $email,
	'firstName' => 'Test',
	]);

$account = new Bf_Account($client, [
	'profile' => $profile,
	]);

$createdAcc = $account
->create();
$createdAccID = $createdAcc->id;


//-- make payment method, and associate it with account
	//-- make Authorize.net token to associate payment method to

$customerProfileID = 28476855;
$customerPaymentProfileID = 25879733;

$authorizeNetToken = new Bf_AuthorizeNetToken($client, [
	'accountID' => $createdAccID,
	'customerProfileID' => $customerProfileID,
	'customerPaymentProfileID' => $customerPaymentProfileID,
	]);

$createdAuthorizeNetToken = $authorizeNetToken
->create();
$createdAuthorizeNetTokenID = $createdAuthorizeNetToken
->id;

$paymentMethod = new Bf_PaymentMethod($client, [
	'linkID' => $createdAuthorizeNetTokenID,
	'accountID' => $createdAccID,
	'name' => 'Authorize.Net',
	'description' => 'Pay via Authorize.Net',
	'gateway' => 'authorizeNet',
	'userEditable' => 0,
	'priority' => 100,
	'reusable' => 1,
	]);
$createdPaymentMethod = $paymentMethod
->create();
$createdPaymentMethodID = $createdPaymentMethod->id;

$paymentMethods = [$createdPaymentMethod];

// add these payment methods to our model of the created account
$createdAcc
->paymentMethods = $paymentMethods;
// save changes to real account
$createdAcc = $createdAcc
->save();

var_export($createdAcc);

//-- Make unit of measure
$uom = new Bf_UnitOfMeasure($client, [
	'name' => 'Devices',
	'displayedAs' => 'Devices',
	'roundingScheme' => 'UP',
	]);
$createdUom = $uom
->create();
$createdUomID = $createdUom->id;

//-- Make product
$product = new Bf_Product($client, [
	'productType' => 'non-recurring',
	'state' => 'prod',
	'name' => 'Month of Paracetamoxyfrusebendroneomycin',
	'description' => 'It can cure the common cold, and being struck by lightning',
	'durationPeriod' => 'days',
	'duration' => 28,
	]);
$createdProduct = $product
->create();
$createdProductID = $createdProduct->id;

//-- Make product rate plan
	//-- Make pricing components for product rate plan
		//-- Make tiers for pricing component
$tier = new Bf_PricingComponentTier($client, [
	'lowerThreshold' => 1,
	'upperThreshold' => 1,
	'pricingType' => 'unit',
	'price' => 1,
	]);
$tiers = [$tier];

$pricingComponentsArray = [
	new Bf_PricingComponent($client, [
	'@type' => 'flatPricingComponent',
	'chargeModel' => 'flat',
	'name' => 'Devices used',
	'description' => 'How many devices you use, I guess',
	'unitOfMeasureID' => $createdUomID,
	'chargeType' => 'subscription',
	'upgradeMode' => 'immediate',
	'downgradeMode' => 'immediate',
	'defaultQuantity' => 10,
	'tiers' => $tiers
	])
];

$prp = new Bf_ProductRatePlan($client, [
	'currency' => 'USD',
	'name' => getUsualPrpName(),
	'pricingComponents' => $pricingComponentsArray,
	'productID' => $createdProductID,
	]);
$createdPrp = $prp
->create();
$createdProductRatePlanID = $createdPrp->id;
$createdPricingComponentID = $createdPrp->pricingComponents[0]->id;

//-- Make pricing component value instance of pricing component
$prc = new Bf_PricingComponentValue($client, [
	'pricingComponentID' => $createdPricingComponentID,
	'value' => 2,
	'crmID' => ''
	]);
$pricingComponentValuesArray = [$prc];


//-- Make Bf_PaymentMethodSubscriptionLinks
// refer by ID to our payment method.
$paymentMethodReference = new Bf_PaymentMethod($client, [
		'id' => $createdPaymentMethodID 
		]);

$paymentMethodSubscriptionLink = new Bf_PaymentMethodSubscriptionLink($client, [
	// 'paymentMethodID' => $createdPaymentMethodID,
	'paymentMethod' => $paymentMethodReference,
	'organizationID' => $firstOrgID,
	]);
$paymentMethodSubscriptionLinks = [$paymentMethodSubscriptionLink];

//-- Make subscription
$sub = new Bf_Subscription($client, [
	'type' => 'Subscription',
	'productID' => $createdProductID,
	'productRatePlanID' => $createdProductRatePlanID,
	'accountID' => $createdAccID,
	'name' => 'Memorable Bf_Subscription',
	'description' => 'Memorable Bf_Subscription Description',
	'paymentMethodSubscriptionLinks' => $paymentMethodSubscriptionLinks,
	'pricingComponentValues' => $pricingComponentValuesArray
	]);
$createdSub = $sub
->create();

echo "\n";
echo sprintf("\$usualLoginAccountID = '%s';\n", $foundLoginAccount->id);
echo sprintf("\$usualLoginUserID = '%s';\n", $foundLoginAccount->userID);
echo sprintf("\$usualOrganisationID = '%s';\n", $firstOrgID);
echo sprintf("\$usualAccountID = '%s';\n", $createdAccID);
echo sprintf("\$usualPaymentMethodLinkID = '%s';\n", $createdAuthorizeNetTokenID);
echo sprintf("\$usualPaymentMethodID = '%s';\n", $createdPaymentMethodID);
echo sprintf("\$usualProductID = '%s';\n", $createdProductID);
echo sprintf("\$usualProductRatePlanID = '%s';\n", $createdProductRatePlanID);
echo sprintf("\$usualPricingComponentID = '%s';\n", $createdPricingComponentID);
echo sprintf("\$usualSubscriptionID = '%s';\n", $createdSub->id);
echo sprintf("\$usualUnitOfMeasureID = '%s';\n", $createdUomID);