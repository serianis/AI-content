<?php

namespace AutoblogAI\Core;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Scheduler {

    const DAILY_EVENT_HOOK = 'autoblogai_daily_publish_event';
    const QUEUE_OPTION_KEY = 'autoblogai_publish_queue';
    const DAILY_CAP_OPTION = 'autoblogai_daily_publish_cap';
    const LAST_PUBLISH_DATE = 'autoblogai_last_publish_date';

    private $is_running = false;

    public function __construct() {
        add_action( self::DAILY_EVENT_HOOK, array( $this, 'process_scheduled_posts' ) );
    }

    public function schedule_events() {
        if ( ! wp_next_scheduled( self::DAILY_EVENT_HOOK ) ) {
            wp_schedule_event( time(), 'daily', self::DAILY_EVENT_HOOK );
        }
    }

    public function unschedule_events() {
        $timestamp = wp_next_scheduled( self::DAILY_EVENT_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::DAILY_EVENT_HOOK );
        }
    }

    public function get_admin_schedules() {
        return array(
            'daily'           => array( 'label' => 'Daily' ),
            'twicedaily'      => array( 'label' => 'Twice Daily' ),
            'hourly'          => array( 'label' => 'Hourly' ),
        );
    }

    public function get_schedule_status() {
        $timestamp = wp_next_scheduled( self::DAILY_EVENT_HOOK );
        return array(
            'is_scheduled' => false !== $timestamp,
            'next_run'     => $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null,
            'queue_count'  => $this->get_queued_topics_count(),
            'daily_cap'    => $this->get_daily_publish_cap(),
            'posts_today'  => $this->get_posts_published_today(),
        );
    }

    public function set_daily_publish_cap( $cap ) {
        if ( ! is_numeric( $cap ) || $cap < 0 ) {
            return new WP_Error( 'invalid_cap', 'Daily publish cap must be a non-negative number' );
        }
        update_option( self::DAILY_CAP_OPTION, intval( $cap ) );
        return true;
    }

    public function get_daily_publish_cap() {
        return intval( get_option( self::DAILY_CAP_OPTION, 0 ) );
    }

    public function get_posts_published_today() {
        $today = date( 'Y-m-d', current_time( 'timestamp' ) );
        $last_run = get_option( self::LAST_PUBLISH_DATE );
        
        if ( $last_run && substr( $last_run, 0, 10 ) === $today ) {
            $queue = get_option( self::QUEUE_OPTION_KEY, array() );
            if ( is_array( $queue ) ) {
                return count( $queue );
            }
        }
        return 0;
    }

    public function enqueue_topic( $topic, $keyword = '' ) {
        $queue = get_option( self::QUEUE_OPTION_KEY, array() );
        if ( ! is_array( $queue ) ) {
            $queue = array();
        }

        $queue[] = array(
            'topic'   => sanitize_text_field( $topic ),
            'keyword' => sanitize_text_field( $keyword ),
            'queued_at' => current_time( 'mysql' ),
        );

        update_option( self::QUEUE_OPTION_KEY, $queue );
        return true;
    }

    public function dequeue_topic() {
        $queue = get_option( self::QUEUE_OPTION_KEY, array() );
        if ( ! is_array( $queue ) || empty( $queue ) ) {
            return false;
        }

        $topic = array_shift( $queue );
        update_option( self::QUEUE_OPTION_KEY, $queue );
        return $topic;
    }

    public function get_queued_topics() {
        $queue = get_option( self::QUEUE_OPTION_KEY, array() );
        return is_array( $queue ) ? $queue : array();
    }

    public function get_queued_topics_count() {
        $queue = $this->get_queued_topics();
        return count( $queue );
    }

    public function clear_queue() {
        delete_option( self::QUEUE_OPTION_KEY );
        return true;
    }

    public function process_scheduled_posts() {
        if ( $this->is_running ) {
            return new WP_Error( 'already_running', 'Scheduler is already running' );
        }

        $this->is_running = true;

        try {
            $daily_cap = $this->get_daily_publish_cap();
            $published_today = $this->get_posts_published_today();

            if ( $daily_cap > 0 && $published_today >= $daily_cap ) {
                $this->is_running = false;
                return new WP_Error( 'daily_cap_reached', 'Daily publish cap has been reached' );
            }

            $topic = $this->dequeue_topic();
            if ( ! $topic ) {
                $this->is_running = false;
                return new WP_Error( 'no_topics', 'No topics in queue' );
            }

            do_action( 'autoblogai_process_queued_topic', $topic );

            update_option( self::LAST_PUBLISH_DATE, current_time( 'mysql' ) );

            $this->is_running = false;
            return true;

        } catch ( Exception $e ) {
            $this->is_running = false;
            return new WP_Error( 'process_error', $e->getMessage() );
        }
    }
}
