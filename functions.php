<?php

function dpl_vend_stocks_add_custom_cron_schedule($schedules){

    $schedules['every_30_minutes'] = array(
        'interval' => 30 * MINUTE_IN_SECONDS,
        'display'  => __( 'Every 30 minutes','dpl-vend-stocks' )
    );

    return $schedules;
}