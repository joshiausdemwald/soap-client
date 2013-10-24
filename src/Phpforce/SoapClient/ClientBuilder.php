<?php
namespace Phpforce\SoapClient;

use Phpforce\SoapClient\Soap\SoapClientFactory;
use Phpforce\SoapClient\Soap\WSDL\Wsdl;
use Phpforce\SoapClient\Plugin\LogPlugin;
use Psr\Log\LoggerInterface;

/**
 * Salesforce SOAP client builder
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ClientBuilder
{
    protected $log;

    /**
     * Construct client builder with required parameters
     *
     * @param Wsdl   $wsdl     Path to your Salesforce WSDL
     * @param string $username Your Salesforce username
     * @param string $password Your Salesforce password
     * @param string $token    Your Salesforce security token
     */
    public function __construct(Wsdl $wsdl, $username, $password, $token)
    {
        $this->wsdl = $wsdl;
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
    }

    /**
     * Enable logging
     *
     * @param LoggerInterface $log Logger
     *
     * @return ClientBuilder
     */
    public function withLog(LoggerInterface $log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Build the Salesforce SOAP client
     *
     * @return Client
     */
    public function build()
    {
        $soapClientFactory = new SoapClientFactory();
        $soapClient = $soapClientFactory->getInstance($this->wsdl);

        if($this->wsdl->getTns() === Wsdl::TNS_ENTERPRISE)
        {
            $client = new EnterpriseClient($soapClient, $this->username, $this->password, $this->token);
        }
        elseif($this->wsdl->getTns() === Wsdl::TNS_PARTNER)
        {
            $client = new PartnerClient($soapClient, $this->username, $this->password, $this->token);
        }
        else
        {
            throw new \UnexpectedValueException(sprintf('Wsdl with target namespace "%s" not supported.', $this->wsdl->getTns()));
        }

        if ($this->log)
        {
            $logPlugin = new LogPlugin($this->log);
            $client->getEventDispatcher()->addSubscriber($logPlugin);
        }

        return $client;
    }
}

