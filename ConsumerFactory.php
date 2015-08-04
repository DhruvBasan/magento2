<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Amqp;

use Magento\Framework\Amqp\Config\Data as QueueConfig;
use Magento\Framework\Amqp\Config\Converter as QueueConfigConverter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;

/**
 * Class which creates Consumers
 */
class ConsumerFactory
{
    /**
     * All of the merged queue config information
     *
     * @var QueueConfig
     */
    private $queueConfig;

    /**
     * @var ConsumerInterface[]
     */
    private $consumers;

    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    private $objectManager = null;

    /**
     * Initialize dependencies.
     *
     * <type name="Magento\Framework\Amqp\ConsumerFactory">
     *     <arguments>
     *         <argument name="consumers" xsi:type="array">
     *             <item name="rabbitmq" xsi:type="array">
     *                 <item name="type" xsi:type="string">Magento\RabbitMq\Model\Consumer</item>
     *                 <item name="connectionName" xsi:type="string">rabbitmq</item>
     *             </item>
     *         </argument>
     *     </arguments>
     * </type>
     *
     * @param QueueConfig $queueConfig
     * @param ObjectManagerInterface $objectManager
     * @param ConsumerInterface[] $consumers
     */
    public function __construct(
        QueueConfig $queueConfig,
        ObjectManagerInterface $objectManager,
        $consumers = []
    ) {
        $this->queueConfig = $queueConfig;
        $this->objectManager = $objectManager;
        $this->consumers = [];

        foreach ($consumers as $consumerConfig) {
            $this->add($consumerConfig['connectionName'], $consumerConfig['type']);
        }
    }

    /**
     * Return the actual Consumer implementation for the given consumer name.
     *
     * @param $consumerName
     * @return ConsumerInterface
     * @throws LocalizedException
     */
    public function get($consumerName)
    {
        $consumerConfig = $this->getConsumerConfigForName($consumerName);
        $consumer = $this->createConsumerForConnectionName($consumerConfig[QueueConfigConverter::CONSUMER_CONNECTION]);
        return $consumer;
    }

    /**
     * Add consumer.
     *
     * @param string $name
     * @param string $typeName
     * @return $this
     */
    private function add($name, $typeName)
    {
        $this->consumers[$name] = $typeName;
        return $this;
    }

    /**
     * Return an instance of a consumer for a connection name.
     *
     * @param string $connectionName
     * @return ConsumerInterface
     * @throws LocalizedException
     */
    private function createConsumerForConnectionName($connectionName)
    {
        if (isset($this->consumers[$connectionName])) {
            $typeName =  $this->consumers[$connectionName];
            return $this->objectManager->create($typeName, []);
        }
        throw new LocalizedException(
            new Phrase('Could not find an implementation type for connection "%name".', ['name' => $connectionName])
        );
    }

    /**
     * Returns the consumer configuration information.
     *
     * @param string $consumerName
     * @return array
     * @throws LocalizedException
     */
    private function getConsumerConfigForName($consumerName)
    {
        $queueConfig = $this->queueConfig->get();
        if (isset($queueConfig[QueueConfigConverter::CONSUMERS][$consumerName])) {
            return $queueConfig[QueueConfigConverter::CONSUMERS][$consumerName];
        }
        throw new LocalizedException(
            new Phrase('Specified consumer "%consumer" is not declared.', ['consumer' => $consumerName])
        );
    }
}
