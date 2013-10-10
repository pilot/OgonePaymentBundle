<?php

namespace Pilot\OgonePaymentBundle\Feedback;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Pilot\OgonePaymentBundle\Config\ConfigurationContainer;
use Pilot\OgonePaymentBundle\Entity\OgoneOrder;
use Pilot\OgonePaymentBundle\Entity\OgoneAlias;
use Pilot\OgonePaymentBundle\Feedback\OgoneCodes;

class TransactionFeedbacker
{
    protected $secureConfigurationContainer;

    protected $om;

    public function __construct(Request $request, ConfigurationContainer $secureConfigurationContainer, ObjectManager $om)
    {
        $this->request = $request;
        $this->secureConfigurationContainer = $secureConfigurationContainer;
        $this->om = $om;
    }

    public function isValidCall()
    {
        if (!$this->request->request->has('SHASIGN') && !$this->request->query->has('SHASIGN')) {
            return false;
        }
        // Check sign
        $toSign = array();
        if ($this->request->query->has('SHASIGN')) {
            foreach ($this->request->query->all() as $key => $val) {
                if($val != '') {
                    $toSign[strtoupper($key)] = $val;
                }
            }
        } else {
            foreach ($this->request->request->all() as $key => $val) {
                if($val != '') {
                    $toSign[strtoupper($key)] = $val;
                }
            }
        }

        unset($toSign["SHASIGN"]);
        ksort($toSign);

        $toHash = '';
        foreach ($toSign as $key => $val) {
            $toHash .= $key.'='.$val.$this->secureConfigurationContainer->getShaOutKey();
        }

        return $this->request->get('SHASIGN') === strtoupper(hash($this->secureConfigurationContainer->getAlgorithm(), $toHash));
    }

    public function hasOrder()
    {
        return $this->request->get('orderID') != '';
    }

    public function updateOrder()
    {
        $order = $this->om->getRepository('PilotOgonePaymentBundle:OgoneOrder')->find($this->request->get('orderID'));

        if (!$order) {
            throw new \LogicException('Order cant be invalid here !!');
        }

        $order
            ->setCurrency($this->request->get('currency'))
            ->setPaymentMethod($this->request->get('PM'))
            ->setAcceptance($this->request->get('ACCEPTANCE'))
            ->setStatus($this->request->get('STATUS'))
            ->setCardNumber($this->request->get('CARDNO'))
            ->setEd($this->request->get('ED'))
            ->setClientName($this->request->get('CN'))
            ->setTransactionDate(\DateTime::createFromFormat('m/d/y', $this->request->get('TRXDATE')))
            ->setPayid($this->request->get('PAYID'))
            ->setNcError($this->request->get('NCERROR'))
            ->setBrand($this->request->get('BRAND'))
            ->setIp($this->request->get('IP'))
        ;

        $this->save($order);

        return $this;
    }

    public function hasAlias()
    {
        return $this->request->get('ALIAS') != '';
    }

    public function updateAlias()
    {
        $alias = $this->om->getRepository('PilotOgonePaymentBundle:OgoneAlias')->find($this->request->get('ALIAS'));

        if (!$alias) {
            throw new \LogicException('Alias cant be invalid here !!');
        }

        if (OgoneCodes::isPayed($this->request->get('STATUS'))) {
            $alias->setStatus(OgoneAlias::STATUS_ACTIVE);
        } elseif (OgoneCodes::isRefused($this->request->get('STATUS'))) {
            $alias->setStatus(OgoneAlias::STATUS_ERROR);
        }

        // Update client info if user change is name for the cb
        if ($this->request->get('CN')) {
            $client = $alias->getClient();
            $client->setFullName($this->request->get('CN'));
            $this->save($client);
        }

        $this->save($alias);

        return $this;
    }

    public function save($object)
    {
        $this->om->persist($object);
        $this->om->flush();
    }
}
