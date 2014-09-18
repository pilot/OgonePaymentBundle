<?php

namespace Pilot\OgonePaymentBundle\Tests\Builder;

use Symfony\Component\Form\FormFactory;
use Pilot\OgonePaymentBundle\Tests\TestCase;
use Pilot\OgonePaymentBundle\Builder\TransactionBuilder;
use Pilot\OgonePaymentBundle\Builder\TransactionFormBuilder;
use Pilot\OgonePaymentBundle\Config\SecureConfigurationContainer;
use Pilot\OgonePaymentBundle\Config\ConfigurationContainer;
use Pilot\OgonePaymentBundle\Entity\OgoneAlias;
use Pilot\OgonePaymentBundle\Entity\OgoneOrder;
use Pilot\OgonePaymentBundle\Batch\TransactionManager;

class TransactionBuilderTest extends TestCase
{
    protected $configurationContainer;

    protected $builder;

    public function setUp()
    {
        $formFactory = new FormFactory($this->getContainer()->get('form.registry'), $this->getContainer()->get('form.resolved_type_factory'));
        $secureConfigurationContainer = new SecureConfigurationContainer(array('shaInKey' => 'testHash', 'algorithm' => 'sha512'));
        $this->configurationContainer = new ConfigurationContainer(array());
        $order = $this->getMock('Pilot\OgonePaymentBundle\Entity\OgoneOrder');
        $tm = new TransactionManager($this->configurationContainer, $this->getContainer()->get('ogone.batch_request'));

        $formBuilder = new TransactionFormBuilder($formFactory, $secureConfigurationContainer);
        $this->builder = $this->getMock(
            'Pilot\OgonePaymentBundle\Builder\TransactionBuilder',
            array('getConfigurationContainer', 'order'),
            array($formBuilder, $this->configurationContainer, $tm, $this->getContainer()->get('doctrine.orm.entity_manager'))
        );
    }

    public function testUseAlias()
    {
        $alias = new OgoneAlias();
        $alias->setName('USAGE');
        $alias->setOperation(OgoneAlias::OPERATION_BYMERCHANT);

        $this->assertEquals(array(), $this->configurationContainer->all());

        $this->builder->useAlias($alias);
        $this->assertEquals(array(
            'aliasoperation' => 'BYMERCHANT',
            'aliasusage'     => 'USAGE',
        ), $this->configurationContainer->all());
    }

    public function testConfigure()
    {
        $this->assertEquals(array(), $this->configurationContainer->all());

        $this->builder
            ->configure()
                ->setBgColor('red')
            ->end();

        $this->assertEquals(array('bgcolor' => 'red'), $this->configurationContainer->all());
    }

    /**
     * @todo: update date to check configuration preparation
     */
    public function prepareTransaction()
    {
        $this->builder
            ->order()
                ->setAmount(150)
            ->end();

        $this->assertEquals(null, $this->configurationContainer->all());

        $this->builder->prepareTransaction();

        $this->assertEquals(array('amount' => 150), $this->configurationContainer->all());
    }
}
