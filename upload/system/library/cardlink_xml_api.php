<?php
/**
 * Cardlink VPOS XML API Client
 *
 * Standalone library for handling Cardlink payment gateway secondary XML requests:
 * - Capture (settle pre-authorized transactions)
 * - Refund (refund captured transactions)
 * - Cancel/Void (cancel pre-authorized transactions)
 * - Status (query transaction status)
 *
 * Based on Cardlink VPOS XML API v2.1
 */
class CardlinkXmlApi
{
    const XML_NAMESPACE     = 'http://www.modirum.com/schemas/vposxmlapi41';
    const XML_NAMESPACE_NS2 = 'http://www.w3.org/2000/09/xmldsig#';
    const API_VERSION       = '2.1';

    const PARTNER_CARDLINK  = 'cardlink';
    const PARTNER_NEXI      = 'nexi';
    const PARTNER_WORLDLINE = 'worldline';

    const ENV_PRODUCTION = 'production';
    const ENV_SANDBOX    = 'sandbox';

    const STATUS_CAPTURED           = 'CAPTURED';
    const STATUS_PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';
    const STATUS_AUTHORIZED         = 'AUTHORIZED';
    const STATUS_CANCELED           = 'CANCELED';
    const STATUS_REFUSED            = 'REFUSED';
    const STATUS_ERROR              = 'ERROR';
    const STATUS_PROCESSING         = 'PROCESSING';

    private $merchantId;
    private $sharedSecret;
    private $businessPartner;
    private $environment;
    private $timeout     = 60;
    private $debug       = false;
    private $debugLogger = null;
    private $lastRequest  = [];
    private $lastResponse = [];

    public function __construct(
        $merchantId,
        $sharedSecret,
        $businessPartner = self::PARTNER_CARDLINK,
        $environment     = self::ENV_SANDBOX
    ) {
        $this->merchantId      = $merchantId;
        $this->sharedSecret    = $sharedSecret;
        $this->businessPartner = $businessPartner;
        $this->environment     = $environment;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setDebug($debug, $logger = null)
    {
        $this->debug       = $debug;
        $this->debugLogger = $logger;
        return $this;
    }

    public function getApiUrl()
    {
        $isProd = ($this->environment === self::ENV_PRODUCTION);

        switch ($this->businessPartner) {
            case self::PARTNER_NEXI:
                return $isProd
                    ? 'https://www.alphaecommerce.gr/vpos/xmlpayvpos'
                    : 'https://alphaecommerce-test.cardlink.gr/vpos/xmlpayvpos';

            case self::PARTNER_WORLDLINE:
                return $isProd
                    ? 'https://vpos.eurocommerce.gr/vpos/xmlpayvpos'
                    : 'https://eurocommerce-test.cardlink.gr/vpos/xmlpayvpos';

            case self::PARTNER_CARDLINK:
            default:
                return $isProd
                    ? 'https://ecommerce.cardlink.gr/vpos/xmlpayvpos'
                    : 'https://ecommerce-test.cardlink.gr/vpos/xmlpayvpos';
        }
    }

    public function capture($orderId, $amount, $currency = 'EUR')
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildCaptureRequestContent($orderId, $amount, $currency);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'CaptureResponse');
    }

    public function refund($orderId, $amount, $currency = 'EUR')
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildRefundRequestContent($orderId, $amount, $currency);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'RefundResponse');
    }

    public function cancel($orderId, $amount, $currency = 'EUR')
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildCancelRequestContent($orderId, $amount, $currency);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'CancelResponse');
    }

    public function void($orderId, $amount, $currency = 'EUR')
    {
        return $this->cancel($orderId, $amount, $currency);
    }

    public function status($orderId)
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildStatusRequestContent($orderId);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'StatusResponse');
    }

    public function walletSale($orderId, $amount, $currency, $walletPaymentData, $payMethod, $orderDesc = '')
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildWalletSaleRequestContent($orderId, $amount, $currency, $walletPaymentData, $payMethod, $orderDesc);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'SaleResponse');
    }

    public function walletSaleWith3DS($orderId, $amount, $currency, $preparedTxId, $payMethod, array $threeDSData, $orderDesc = '')
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildWalletSale3DSRequestContent($orderId, $amount, $currency, $preparedTxId, $payMethod, $threeDSData, $orderDesc);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'SaleResponse');
    }

    public function walletSession($orderId, $amount, $currency, $validationUrl, $orderDesc = '')
    {
        $messageId      = $this->generateMessageId();
        $timestamp      = $this->generateTimestamp();
        $messageContent = $this->buildWalletSessionRequestContent($orderId, $amount, $currency, $validationUrl, $orderDesc);
        $xml            = $this->buildXmlRequest($messageId, $timestamp, $messageContent);
        return $this->sendRequest($xml, 'WalletResponse');
    }

    private function buildWalletSaleRequestContent($orderId, $amount, $currency, $walletPaymentData, $payMethod, $orderDesc = '')
    {
        $mid             = $this->merchantId;
        $escapedData     = htmlspecialchars($walletPaymentData, ENT_XML1, 'UTF-8');
        $attrName        = ($payMethod === 'applepay') ? 'applePaymentData' : 'googlePaymentData';

        return "    <SaleRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <OrderInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "            <OrderDesc>{$orderDesc}</OrderDesc>\n" .
               "            <OrderAmount>{$amount}</OrderAmount>\n" .
               "            <Currency>{$currency}</Currency>\n" .
               "        </OrderInfo>\n" .
               "        <PaymentInfo>\n" .
               "            <PayMethod>{$payMethod}</PayMethod>\n" .
               "        </PaymentInfo>\n" .
               "        <WalletInfo>\n" .
               "            <Attribute name=\"{$attrName}\">{$escapedData}</Attribute>\n" .
               "        </WalletInfo>\n" .
               "    </SaleRequest>";
    }

    private function buildWalletSale3DSRequestContent($orderId, $amount, $currency, $preparedTxId, $payMethod, array $threeDSData, $orderDesc = '')
    {
        $mid                  = $this->merchantId;
        $enrollmentStatus     = htmlspecialchars($threeDSData['enrollmentStatus'] ?? '', ENT_XML1, 'UTF-8');
        $authenticationStatus = htmlspecialchars($threeDSData['authenticationStatus'] ?? '', ENT_XML1, 'UTF-8');
        $cavv                 = htmlspecialchars($threeDSData['cavv'] ?? '', ENT_XML1, 'UTF-8');
        $xid                  = htmlspecialchars($threeDSData['xid'] ?? '', ENT_XML1, 'UTF-8');
        $eci                  = htmlspecialchars($threeDSData['eci'] ?? '', ENT_XML1, 'UTF-8');
        $protocol             = htmlspecialchars($threeDSData['protocol'] ?? '', ENT_XML1, 'UTF-8');
        $protocolElement      = $protocol !== '' ? "                <Protocol>{$protocol}</Protocol>\n" : '';

        return "    <SaleRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <OrderInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "            <OrderDesc>{$orderDesc}</OrderDesc>\n" .
               "            <OrderAmount>{$amount}</OrderAmount>\n" .
               "            <Currency>{$currency}</Currency>\n" .
               "        </OrderInfo>\n" .
               "        <PaymentInfo preparedTxId=\"{$preparedTxId}\">\n" .
               "            <PayMethod>{$payMethod}</PayMethod>\n" .
               "            <ThreeDSecure>\n" .
               "                <EnrollmentStatus>{$enrollmentStatus}</EnrollmentStatus>\n" .
               "                <AuthenticationStatus>{$authenticationStatus}</AuthenticationStatus>\n" .
               "                <CAVV>{$cavv}</CAVV>\n" .
               "                <XID>{$xid}</XID>\n" .
               "                <ECI>{$eci}</ECI>\n" .
               $protocolElement .
               "            </ThreeDSecure>\n" .
               "        </PaymentInfo>\n" .
               "        <WalletInfo>\n" .
               "            <Attribute></Attribute>\n" .
               "        </WalletInfo>\n" .
               "    </SaleRequest>";
    }

    private function buildWalletSessionRequestContent($orderId, $amount, $currency, $validationUrl, $orderDesc = '')
    {
        $mid                 = $this->merchantId;
        $escapedValidationUrl = htmlspecialchars($validationUrl, ENT_XML1, 'UTF-8');

        return "    <WalletRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <OrderInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "            <OrderDesc>{$orderDesc}</OrderDesc>\n" .
               "            <OrderAmount>{$amount}</OrderAmount>\n" .
               "            <Currency>{$currency}</Currency>\n" .
               "        </OrderInfo>\n" .
               "        <WalletId>ApplePay</WalletId>\n" .
               "        <Mid>{$mid}</Mid>\n" .
               "        <ValidationURL>{$escapedValidationUrl}</ValidationURL>\n" .
               "    </WalletRequest>";
    }

    private function buildCaptureRequestContent($orderId, $amount, $currency)
    {
        $mid         = $this->merchantId;
        $orderAmount = $this->formatAmount($amount);
        return "    <CaptureRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <OrderInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "            <OrderAmount>{$orderAmount}</OrderAmount>\n" .
               "            <Currency>{$currency}</Currency>\n" .
               "        </OrderInfo>\n" .
               "    </CaptureRequest>";
    }

    private function buildRefundRequestContent($orderId, $amount, $currency)
    {
        $mid         = $this->merchantId;
        $orderAmount = $this->formatAmount($amount);
        return "    <RefundRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <OrderInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "            <OrderAmount>{$orderAmount}</OrderAmount>\n" .
               "            <Currency>{$currency}</Currency>\n" .
               "        </OrderInfo>\n" .
               "    </RefundRequest>";
    }

    private function buildCancelRequestContent($orderId, $amount, $currency)
    {
        $mid         = $this->merchantId;
        $orderAmount = $this->formatAmount($amount);
        return "    <CancelRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <OrderInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "            <OrderAmount>{$orderAmount}</OrderAmount>\n" .
               "            <Currency>{$currency}</Currency>\n" .
               "        </OrderInfo>\n" .
               "    </CancelRequest>";
    }

    private function buildStatusRequestContent($orderId)
    {
        $mid = $this->merchantId;
        return "    <StatusRequest>\n" .
               "        <Authentication>\n" .
               "            <Mid>{$mid}</Mid>\n" .
               "        </Authentication>\n" .
               "        <TransactionInfo>\n" .
               "            <OrderId>{$orderId}</OrderId>\n" .
               "        </TransactionInfo>\n" .
               "    </StatusRequest>";
    }

    private function buildXmlRequest($messageId, $timestamp, $requestContent)
    {
        $xmlNamespace    = self::XML_NAMESPACE;
        $xmlNamespaceNs2 = self::XML_NAMESPACE_NS2;
        $apiVersion      = self::API_VERSION;

        $messageXml  = '<Message xmlns="' . $xmlNamespace . '" xmlns:ns2="' . $xmlNamespaceNs2 . '" messageId="' . $messageId . '" timeStamp="' . $timestamp . '" version="' . $apiVersion . '">';
        $messageXml .= "\n" . $requestContent . "\n";
        $messageXml .= '</Message>';

        $digest = $this->calculateDigest($messageXml);

        $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<VPOS xmlns="' . $xmlNamespace . '" xmlns:ns2="' . $xmlNamespaceNs2 . '">' . "\n";
        $xml .= '<Message messageId="' . $messageId . '" timeStamp="' . $timestamp . '" version="' . $apiVersion . '">' . "\n";
        $xml .= $requestContent . "\n";
        $xml .= '</Message>' . "\n";
        $xml .= '<Digest>' . $digest . '</Digest>' . "\n";
        $xml .= '</VPOS>';

        return $xml;
    }

    private function calculateDigest($messageXml)
    {
        $canonicalized = $this->canonicalizeXml($messageXml);
        $this->log('Canonicalized: ' . $canonicalized);
        $dataToHash = $canonicalized . $this->sharedSecret;
        $digest     = base64_encode(hash('sha256', $dataToHash, true));
        $this->log('Digest: ' . $digest);
        return $digest;
    }

    private function canonicalizeXml($xml)
    {
        $xml = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $xml);
        return trim($xml);
    }

    private function sendRequest($xml, $expectedResponseType)
    {
        $url = $this->getApiUrl();

        $this->lastRequest = [
            'url'                  => $url,
            'xml'                  => $xml,
            'timestamp'            => date('Y-m-d H:i:s'),
            'expectedResponseType' => $expectedResponseType,
        ];

        $this->log('REQUEST URL: ' . $url);
        $this->log('REQUEST XML: ' . $xml);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/xml; charset=UTF-8',
                'Accept: application/xml',
                'Content-Length: ' . strlen($xml),
            ],
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        $curlErrno    = curl_errno($ch);
        curl_close($ch);

        $this->lastResponse = [
            'http_code'  => $httpCode,
            'body'       => $responseBody,
            'curl_error' => $curlError,
            'curl_errno' => $curlErrno,
            'timestamp'  => date('Y-m-d H:i:s'),
        ];

        $this->log('RESPONSE HTTP: ' . $httpCode . ' | cURL errno: ' . $curlErrno);
        $this->log('RESPONSE BODY: ' . ($responseBody ?: '(empty)'));

        if ($curlErrno !== 0) {
            return new CardlinkXmlResponse(false, [
                'error'      => 'cURL Error: ' . $curlError,
                'curl_errno' => $curlErrno,
                'http_code'  => $httpCode,
            ]);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return new CardlinkXmlResponse(false, [
                'error'         => 'HTTP Error',
                'http_code'     => $httpCode,
                'response_body' => $responseBody,
            ]);
        }

        return $this->parseResponse($responseBody, $expectedResponseType);
    }

    private function parseResponse($responseBody, $expectedResponseType)
    {
        if (empty($responseBody)) {
            return new CardlinkXmlResponse(false, ['error' => 'Empty response body']);
        }

        // Some responses may return JSON instead of XML
        $trimmedBody = trim($responseBody);
        if ($trimmedBody !== '' && ($trimmedBody[0] === '{' || $trimmedBody[0] === '[')) {
            $decodedJson = json_decode($trimmedBody, true);
            if (is_array($decodedJson)) {
                $decodedJson['response_format'] = 'json';
                $this->log('Gateway returned JSON: ' . json_encode($decodedJson));
                return new CardlinkXmlResponse(false, $decodedJson);
            }
        }

        $previousErrorState = libxml_use_internal_errors(true);
        $dom    = new DOMDocument();
        $loaded = $dom->loadXML($responseBody);

        if (!$loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorState);
            return new CardlinkXmlResponse(false, [
                'error'         => 'Failed to parse XML response',
                'xml_errors'    => array_map(function ($e) { return $e->message; }, $errors),
                'response_body' => $responseBody,
            ]);
        }

        libxml_use_internal_errors($previousErrorState);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('v', self::XML_NAMESPACE);

        $data = [];

        $responseNodes = $xpath->query('//v:' . $expectedResponseType);
        if ($responseNodes->length === 0) {
            $responseNodes = $xpath->query('//' . $expectedResponseType);
        }

        if ($expectedResponseType === 'StatusResponse') {
            $detailsNodes = $xpath->query('//v:TransactionDetails | //TransactionDetails');
            if ($detailsNodes->length > 0) {
                $responseNodes = $detailsNodes;
            }
        }

        if ($responseNodes->length > 0) {
            $data = $this->extractNodeData($responseNodes->item(0), $xpath);
        }

        // Handle WalletResponse: extract walletData from nested structure
        if ($expectedResponseType === 'WalletResponse') {
            $walletDataNodes = $xpath->query(
                "//*[translate(local-name(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='walletdata']" .
                "//*[translate(local-name(),'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='value']"
            );
            if ($walletDataNodes->length > 0) {
                $rawValue = trim($walletDataNodes->item(0)->nodeValue);
                if (strpos($rawValue, 'walletData') === 0) {
                    $rawValue = trim(substr($rawValue, strlen('walletData')));
                }
                $data['walletData'] = $rawValue;
            }
            if (empty($data['walletData'])) {
                foreach ($data as $key => $value) {
                    if (strtolower($key) === 'walletdata' && $value !== '') {
                        if (is_array($value) && isset($value['data']['value'])) {
                            $data['walletData'] = trim($value['data']['value']);
                        } else {
                            $data['walletData'] = is_array($value) ? json_encode($value) : $value;
                        }
                        break;
                    }
                }
            }
            if (isset($data['walletData']) && is_array($data['walletData'])) {
                $data['walletData'] = json_encode($data['walletData']);
            }
        }

        if (empty($data)) {
            $errorNodes = $xpath->query('//v:ErrorMessage | //ErrorMessage');
            if ($errorNodes->length > 0) {
                $data = $this->extractNodeData($errorNodes->item(0), $xpath);
            }
        }

        $digestNodes = $xpath->query('//v:Digest | //Digest');
        if ($digestNodes->length > 0) {
            $data['digest'] = $digestNodes->item(0)->nodeValue;
        }

        $this->log('PARSED DATA: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        $status    = $data['Status'] ?? $data['status'] ?? '';
        $isSuccess = in_array(strtoupper($status), [
            self::STATUS_CAPTURED,
            self::STATUS_PARTIALLY_CAPTURED,
            self::STATUS_AUTHORIZED,
            'APPROVED',
            'SUCCESS',
            'REFUNDED',
            'PARTIALLY_REFUNDED',
            'CANCELED',
            'REVERSED',
        ], true);

        if (isset($data['ErrorCode']) || isset($data['errorCode'])) {
            $isSuccess = false;
        }

        return new CardlinkXmlResponse($isSuccess, $data);
    }

    private function extractNodeData(DOMNode $node, DOMXPath $xpath)
    {
        $data = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $nodeName = $child->localName;

            if ($nodeName === 'Attribute') {
                $attrName                   = $child->getAttribute('name');
                $data['attributes'][$attrName] = $child->nodeValue;
                continue;
            }

            $hasChildElements = false;
            foreach ($child->childNodes as $grandChild) {
                if ($grandChild->nodeType === XML_ELEMENT_NODE) {
                    $hasChildElements = true;
                    break;
                }
            }

            $data[$nodeName] = $hasChildElements
                ? $this->extractNodeData($child, $xpath)
                : $child->nodeValue;
        }

        return $data;
    }

    private function generateMessageId()
    {
        return 'M' . (int)(microtime(true) * 1000);
    }

    private function generateTimestamp()
    {
        $dt = new DateTime('now', new DateTimeZone('Europe/Athens'));
        return $dt->format('Y-m-d\TH:i:s.vP');
    }

    private function formatAmount($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    private function log($message)
    {
        if (!$this->debug) {
            return;
        }
        if ($this->debugLogger !== null) {
            call_user_func($this->debugLogger, $message);
        } else {
            error_log('[CardlinkXmlApi] ' . $message);
        }
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}

/**
 * Response object for Cardlink XML API calls.
 */
class CardlinkXmlResponse
{
    private $success;
    private $data;

    public function __construct($success, array $data)
    {
        $this->success = $success;
        $this->data    = $data;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getData()
    {
        return $this->data;
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function getStatus()
    {
        return $this->data['Status'] ?? $this->data['status'] ?? null;
    }

    public function getTransactionId()
    {
        return $this->data['TxId'] ?? $this->data['txId'] ?? null;
    }

    public function getOrderId()
    {
        return $this->data['OrderId'] ?? $this->data['orderId'] ?? null;
    }

    public function getPaymentRef()
    {
        return $this->data['PaymentRef'] ?? $this->data['paymentRef'] ?? null;
    }

    public function getOrderAmount()
    {
        $amount = $this->data['OrderAmount'] ?? $this->data['orderAmount'] ?? null;
        return $amount !== null ? (float)$amount : null;
    }

    public function getPaymentTotal()
    {
        $total = $this->data['PaymentTotal'] ?? $this->data['paymentTotal'] ?? null;
        return $total !== null ? (float)$total : null;
    }

    public function getCurrency()
    {
        return $this->data['Currency'] ?? $this->data['currency'] ?? null;
    }

    public function getDescription()
    {
        return $this->data['Description'] ?? $this->data['description'] ?? null;
    }

    public function getError()
    {
        if ($this->success) {
            return null;
        }
        return $this->data['error']
            ?? $this->data['ErrorMessage']
            ?? $this->data['Description']
            ?? 'Unknown error';
    }

    public function getAttributes()
    {
        return $this->data['attributes'] ?? [];
    }

    public function getAttribute($name, $default = null)
    {
        return $this->data['attributes'][$name] ?? $default;
    }

    public function getSettlementStatus()
    {
        $s = $this->data['attributes']['SettlStatus']
            ?? $this->data['SettlStatus']
            ?? $this->data['settlstatus']
            ?? null;
        return $s !== null ? (int)$s : null;
    }

    public function isSettled()
    {
        $s = $this->getSettlementStatus();
        return $s !== null && $s >= 20;
    }

    public function isInSettlementTransit()
    {
        return $this->getSettlementStatus() === 10;
    }

    public function canVoid()
    {
        return $this->getSettlementStatus() === 0;
    }
}
