<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueName.
 */
final class QueueName
{
    const ADD_LEADS_TO_CAMPAIGN      = 'add_leads_to_campaign';
    const EMAIL_HIT                  = 'email_hit';
    const NEGATIVE_EVENTS_TRIGGER    = 'negative_events_trigger';
    const PAGE_HIT                   = 'page_hit';
    const REMOVE_LEADS_FROM_CAMPAIGN = 'remove_leads_from_campaign';
    const SCHEDULED_EVENTS_TRIGGER   = 'scheduled_events_trigger';
    const STARTING_EVENTS_TRIGGER    = 'starting_events_trigger';
}
