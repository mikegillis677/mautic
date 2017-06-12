<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     * @param EventModel     $campaignEventModel
     * @param CampaignModel  $campaignModel
     */
    public function __construct(
        IpLookupHelper $ipLookupHelper,
        AuditLogModel $auditLogModel,
        EventModel $campaignEventModel,
        CampaignModel $campaignModel
    ) {
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->auditLogModel      = $auditLogModel;
        $this->campaignEventModel = $campaignEventModel;
        $this->campaignModel      = $campaignModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_POST_SAVE      => ['onCampaignPostSave', 0],
            CampaignEvents::CAMPAIGN_POST_DELETE    => ['onCampaignDelete', 0],
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            QueueEvents::NEGATIVE_EVENTS_TRIGGER    => ['onNegativeEventsTrigger', 0],
            QueueEvents::SCHEDULED_EVENTS_TRIGGER   => ['onScheduledEventsTrigger', 0],
            QueueEvents::STARTING_EVENTS_TRIGGER    => ['onStartingEventsTrigger', 0],
            QueueEvents::ADD_LEADS_TO_CAMPAIGN      => ['onAddLeadsToCampaign', 0],
            QueueEvents::REMOVE_LEADS_FROM_CAMPAIGN => ['onRemoveLeadsFromCampaign', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param Events\CampaignEvent $event
     */
    public function onCampaignPostSave(Events\CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        $details  = $event->getChanges();

        //don't set leads
        unset($details['leads']);

        if (!empty($details)) {
            $log = [
                'bundle'    => 'campaign',
                'object'    => 'campaign',
                'objectId'  => $campaign->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param Events\CampaignEvent $event
     */
    public function onCampaignDelete(Events\CampaignEvent $event)
    {
        $campaign = $event->getCampaign();
        $log      = [
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $campaign->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $campaign->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add event triggers and actions.
     *
     * @param Events\CampaignBuilderEvent $event
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event)
    {
        //Add action to actually add/remove lead to a specific lists
        $addRemoveLeadAction = [
            'label'           => 'mautic.campaign.event.addremovelead',
            'description'     => 'mautic.campaign.event.addremovelead_descr',
            'formType'        => 'campaignevent_addremovelead',
            'formTypeOptions' => [
                'include_this' => true,
            ],
            'callback' => '\Mautic\CampaignBundle\Helper\CampaignEventHelper::addRemoveLead',
        ];
        $event->addAction('campaign.addremovelead', $addRemoveLeadAction);
    }

    /**
     * Trigger negative events.
     *
     * @param QueueConsumerEvent $event
     */
    public function onNegativeEventsTrigger(QueueConsumerEvent $event)
    {
        $payload         = $event->getPayload();
        $campaignId      = $payload['campaignId'];
        $start           = $payload['start'];
        $limit           = $payload['limit'];
        $max             = $payload['max'];
        $leadCount       = $payload['leadCount'];
        $totalEventCount = $payload['totalEventCount'];
        $returnCounts    = $payload['returnCounts'];
        $this->campaignEventModel->triggerNegativeEvent(
            $campaignId,
            $start,
            $limit,
            $max,
            $leadCount,
            $totalEventCount,
            $returnCounts
        );
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }

    public function onScheduledEventsTrigger(QueueConsumerEvent $event)
    {
        $payload             = $event->getPayload();
        $campaignId          = $payload['campaignId'];
        $events              = $payload['events'];
        $campaignEvents      = $payload['campaignEvents'];
        $eventSettings       = $payload['eventSettings'];
        $limit               = $payload['limit'];
        $max                 = $payload['max'];
        $totalScheduledCount = $payload['totalScheduledCount'];
        $returnCounts        = $payload['returnCounts'];
        $this->campaignEventModel->triggerScheduledEvent(
            $campaignId,
            $events,
            $campaignEvents,
            $eventSettings,
            $limit,
            $max,
            $totalScheduledCount,
            $returnCounts
        );
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }

    /**
     * Trigger starting events.
     *
     * @param QueueConsumerEvent $event
     */
    public function onStartingEventsTrigger(QueueConsumerEvent $event)
    {
        $payload          = $event->getPayload();
        $campaignId       = $payload['campaignId'];
        $campaignLeads    = $payload['campaignLeads'];
        $limit            = $payload['limit'];
        $max              = $payload['max'];
        $maxCount         = $payload['maxCount'];
        $events           = $payload['events'];
        $decisionChildren = $payload['decisionChildren'];
        $totalEventCount  = $payload['totalEventCount'];
        $returnCounts     = $payload['returnCounts'];
        $this->campaignEventModel->triggerStartingEvent(
            $campaignId,
            $campaignLeads,
            $limit,
            $max,
            $maxCount,
            $events,
            $decisionChildren,
            $totalEventCount,
            $returnCounts
        );
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }

    /**
     * Add leads to a campaign.
     *
     * @param QueueConsumerEvent $event
     */
    public function onAddLeadsToCampaign(QueueConsumerEvent $event)
    {
        $payload     = $event->getPayload();
        $campaign    = $payload['campaign'];
        $newLeadList = $payload['newLeadList'];
        $this->campaignModel->addLeadsToCampaign($campaign, $newLeadList);
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }

    /**
     * Remove leads from a campaign.
     *
     * @param QueueConsumerEvent $event
     */
    public function onRemoveLeadsFromCampaign(QueueConsumerEvent $event)
    {
        $payload        = $event->getPayload();
        $campaign       = $payload['campaign'];
        $removeLeadList = $payload['removeLeadList'];
        $this->campaignModel->removeLeadsFromCampaign($campaign, $removeLeadList);
        $event->setResult(QueueConsumerResults::ACKNOWLEDGE);
    }
}
