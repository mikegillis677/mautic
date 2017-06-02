<?php

/*
 * @copyright   Mautic, Inc
 * @author      Mautic, Inc
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle;

/**
 * Class MauticQueueEvents
 * Events available for MauticQueueBundle.
 */
final class QueueEvents
{
    const CONSUME_MESSAGE = 'mautic.queue_consume_message';

    const PUBLISH_MESSAGE = 'mautic.queue_publish_message';

    const COUNT_MESSAGES = 'mautic.queue_count_messages';

    const BUILD_CONFIG = 'mautic.queue_build_config';

    const ADD_LEADS_TO_CAMPAIGN = 'mautic.queue_add_leads_to_campaign';

    const EMAIL_HIT = 'mautic.queue_email_hit';

    const NEGATIVE_EVENTS_TRIGGER = 'mautic.queue_negative_events_trigger';

    const PAGE_HIT = 'mautic.queue_page_hit';

    const REMOVE_LEADS_FROM_CAMPAIGN = 'mautic.queue_remove_leads_from_campaign';

    const SCHEDULED_EVENTS_TRIGGER = 'mautic.queue_scheduled_events_trigger';

    const STARTING_EVENTS_TRIGGER = 'mautic.queue_starting_events_trigger';
}
