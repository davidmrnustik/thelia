<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Attribute\AttributeAvCreateEvent;
use Thelia\Core\Event\Attribute\AttributeAvDeleteEvent;
use Thelia\Core\Event\Attribute\AttributeAvUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\UpdatePositionEvent;
use Thelia\Model\AttributeAv as AttributeAvModel;
use Thelia\Model\AttributeAvQuery;

class AttributeAv extends BaseAction implements EventSubscriberInterface
{
    /**
     * Create a new attribute entry
     *
     * @param $eventName
     */
    public function create(AttributeAvCreateEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $attribute = new AttributeAvModel();

        $attribute
            ->setAttributeId($event->getAttributeId())
            ->setLocale($event->getLocale())
            ->setTitle($event->getTitle())

            ->save()
        ;

        $event->setAttributeAv($attribute);
    }

    /**
     * Change a product attribute
     *
     * @param $eventName
     */
    public function update(AttributeAvUpdateEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        if (null !== $attribute = AttributeAvQuery::create()->findPk($event->getAttributeAvId())) {
            $attribute
                ->setLocale($event->getLocale())
                ->setTitle($event->getTitle())
                ->setDescription($event->getDescription())
                ->setChapo($event->getChapo())
                ->setPostscriptum($event->getPostscriptum())

                ->save();

            $event->setAttributeAv($attribute);
        }
    }

    /**
     * Delete a product attribute entry
     *
     * @param $eventName
     */
    public function delete(AttributeAvDeleteEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        if (null !== ($attribute = AttributeAvQuery::create()->findPk($event->getAttributeAvId()))) {
            $attribute
                ->delete()
            ;

            $event->setAttributeAv($attribute);
        }
    }

    /**
     * Changes position, selecting absolute ou relative change.
     *
     * @param $eventName
     */
    public function updatePosition(UpdatePositionEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $this->genericUpdatePosition(AttributeAvQuery::create(), $event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::ATTRIBUTE_AV_CREATE          => ["create", 128],
            TheliaEvents::ATTRIBUTE_AV_UPDATE          => ["update", 128],
            TheliaEvents::ATTRIBUTE_AV_DELETE          => ["delete", 128],
            TheliaEvents::ATTRIBUTE_AV_UPDATE_POSITION => ["updatePosition", 128],
        ];
    }
}
