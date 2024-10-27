<?php
/**
 * Plugin Name: Article analytics
 * Plugin URI: https://wordpress.org/plugins/article-analytics/
 * Description: Article analytics provides digital analytics of the visitors who read your posts.
 * Version: 1.0
 * Author: Dinu Guzun
 * License: GPLv2
 */
global $wpdb;
define('WP_ARTICLE_ANALITICS_TABLE', $wpdb->prefix . 'article_analytics_info');
define('WP_ARTICLE_ANALITICS_CONFIG_TABLE', $wpdb->prefix . 'article_analytics_config');

add_filter('pre_get_posts', 'article_analytics_visit_article');

add_action('admin_menu', 'article_analytics_menu');

wp_register_style('style', '/wp-content/plugins/article-analytics/style.css');

check_article_analitics();

function check_article_analitics() {
    global $wpdb;
    $wp_article_analytics_exists = $wp_article_analytics_config_exists = false;
    $tables = $wpdb->get_results("show tables");
    foreach ($tables as $table) {
        foreach ($table as $value) {
            if ($value == WP_ARTICLE_ANALITICS_TABLE) {
                $wp_article_analytics_exists = true;
            }
            if ($value == WP_ARTICLE_ANALITICS_CONFIG_TABLE) {
                $wp_article_analytics_config_exists = true;
            }
        }
    }
    if ($wp_article_analytics_exists == false) {
        $sql = "CREATE TABLE " . WP_ARTICLE_ANALITICS_TABLE . " (
                                ID INT(11) NOT NULL  AUTO_INCREMENT,
                                post_id INT(11) NOT NULL ,
                                user_id INT(11) NOT NULL ,
                                date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                PRIMARY KEY (ID)
                        )";
        $wpdb->get_results($sql);
    }

    if ($wp_article_analytics_config_exists == false) {
        $sql = "CREATE TABLE " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " (
                                config_item VARCHAR(60) NOT NULL ,
                                config_value TEXT NOT NULL ,
                                PRIMARY KEY (config_item)
                        )";
        $wpdb->get_results($sql);
        $sql = "INSERT INTO " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " SET config_item='article_analytics_user_logon', config_value='all'";
        $wpdb->get_results($sql);
        $sql = "INSERT INTO " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " SET config_item='article_analytics_statistic_period', config_value='7'";
        $wpdb->get_results($sql);
        $sql = "INSERT INTO " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " SET config_item='article_analytics_include_owner_visit', config_value='1'";
        $wpdb->get_results($sql);
    }
}

function article_analytics_visit_article($wp_query) {
    $query = $wp_query->query;
    $post_id = (isset($query['p'])) ? $query['p'] : NULL;

    if (!$post_id) {
        return;
    }
    global $wpdb;
    $current_user_id = get_current_user_id();
    $sql = "INSERT INTO " . WP_ARTICLE_ANALITICS_TABLE . " SET user_id=" . $current_user_id . ", post_id=" . $post_id;
    $result = $wpdb->get_results($sql);
}

function article_analytics_menu() {
    global $wpdb;
    $allowed_group = 'manage_options';
    if (function_exists('add_menu_page')) {
        add_menu_page(__('Article analytics', 'article_analytics'), __('Article analytics', 'article_analytics'), $allowed_group, 'article_analytics', 'article_analytics_configuration');
    }
}

function article_analytics_configuration() {
    wp_enqueue_style('style');
    global $wpdb;
    article_analytics_configuration_save();
    $articleAnaliticsConfigArray = $wpdb->get_results("SELECT * FROM " . WP_ARTICLE_ANALITICS_CONFIG_TABLE);
    $configs = array();
    foreach ($articleAnaliticsConfigArray as $articleAnaliticsConfig) {
        $configs[$articleAnaliticsConfig->config_item] = $articleAnaliticsConfig->config_value;
    }
    $userLogon = $statisticPeriod = $incudeOwnerVisit = '';
    if (isset($configs['article_analytics_user_logon'])) {
        $userLogon = $configs['article_analytics_user_logon'];
    }
    if (isset($configs['article_analytics_statistic_period'])) {
        $statisticPeriod = $configs['article_analytics_statistic_period'];
    }
    if (isset($configs['article_analytics_include_owner_visit'])) {
        $incudeOwnerVisit = $configs['article_analytics_include_owner_visit'];
    }
    ?>
    <form  method="post" action="<?php echo bloginfo('wpurl'); ?>/wp-admin/admin.php?page=article_analitics">
        <div class="background">
            <label><?php _e('Period analysis', 'article_analitics'); ?></label>
            <input class="setting-input" type="number" name="article_analytics_statistic_period" value="<?php echo $statisticPeriod; ?>"/>
            <label><?php _e('days', 'article_analitics'); ?></label>
            <br/>
            <label><?php _e('Type of audience', 'article_analitics') ?></label>
            <select name="article_analytics_user_logon" class="setting-input">
                <option value="all" <?php if ($userLogon == 'all') { ?>selected="selected"<?php } ?> ><?php _e('All visits', 'article_analitics') ?></option>
                <option value="authenticated" <?php if ($userLogon == 'authenticated') { ?>selected="selected"<?php } ?> ><?php _e('Authenticated visits', 'article_analitics') ?></option>
                <option value="anonymous" <?php if ($userLogon == 'anonymous') { ?>selected="selected"<?php } ?> ><?php _e('Anonymous visits', 'article_analitics') ?></option>
            </select>
            <br/>
            <label class="setting-label-margin-right"><?php _e('Include owner visits for statistics', 'article_analitics') ?></label>
            <label><?php _e('Yes', 'article_analitics') ?></label>
            <input type="radio" name="article_analytics_include_owner_visit" value="1" <?php if ($incudeOwnerVisit == '1') { ?>checked="true"<?php } ?>/>
            <label><?php _e('No', 'article_analitics') ?></label>
            <input type="radio" name="article_analytics_include_owner_visit" value="0" <?php if ($incudeOwnerVisit == '0') { ?>checked="true"<?php } ?>/>
            <br/>
            <input class="setting-input-submit" type="submit" value="<?php _e('Save', 'article_analitics') ?>"/>
        </div>
    </form>
    <?php
}

function article_analytics_configuration_save() {
    if (isset($_POST['article_analytics_user_logon'])) {
        article_analytics_configuration_save_item('article_analytics_user_logon', $_POST['article_analytics_user_logon']);
    }
    if (isset($_POST['article_analytics_statistic_period'])) {
        article_analytics_configuration_save_item('article_analytics_statistic_period', $_POST['article_analytics_statistic_period']);
    }
    if (isset($_POST['article_analytics_include_owner_visit'])) {
        article_analytics_configuration_save_item('article_analytics_include_owner_visit', $_POST['article_analytics_include_owner_visit']);
    }
}

function article_analytics_configuration_save_item($key, $value) {
    global $wpdb;
    $articleAnalyticsConfigArray = $wpdb->get_results("SELECT * FROM " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " where config_item = '" . $key . "'");
    if (count($articleAnalyticsConfigArray) == 0) {
        $sql = "INSERT INTO " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " SET config_item='" . $key . "', config_value='" . $value . "'";
        $wpdb->get_results($sql);
    } else {
        $sql = "UPDATE " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " SET config_value='" . $value . "' WHERE config_item='" . $key . "'";
        $wpdb->get_results($sql);
    }
}

function article_analytics_configuration_get_item($key) {
    global $wpdb;
    $value = NULL;
    $articleAnalyticsConfigArray = $wpdb->get_results("SELECT * FROM " . WP_ARTICLE_ANALITICS_CONFIG_TABLE . " where config_item = '" . $key . "'");
    if (count($articleAnalyticsConfigArray) > 0) {
        $value = $articleAnalyticsConfigArray[0]->config_value;
    }

    return $value;
}

function article_analytics_get_statistic($period, $userLogon, $ownerVisits) {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $sql = "SELECT " . WP_ARTICLE_ANALITICS_TABLE . ".post_id, " . WP_ARTICLE_ANALITICS_TABLE . ".user_id, count(" . WP_ARTICLE_ANALITICS_TABLE . ".ID) as nr_visits ";
    $sql .= "FROM " . WP_ARTICLE_ANALITICS_TABLE . ", " . $wpdb->posts;
    $sql .= " WHERE (date > DATE_SUB(now(), INTERVAL " . $period . " DAY)) and " . WP_ARTICLE_ANALITICS_TABLE . ".post_id = " . $wpdb->posts . ".ID and ". $wpdb->posts . ".post_author = ".$current_user_id;
    switch ($userLogon) {
        case 'authenticated': {
                $sql .=" and user_id <> 0";
                break;
            }
        case 'anonymous': {
                $sql .=" and user_id = 0";
                break;
            }
    }
    
    if ($ownerVisits == 0) {
        $sql .=" and user_id <> " . $current_user_id;
    }

    $sql .=" GROUP BY post_id ORDER BY nr_visits desc";

    $result = $wpdb->get_results($sql);

    return $result;
}

function analytics_chart_widget_display($args) {
    $htmlWidget = article_analytics_widget_render();
    extract($args);
    echo $before_widget;
    echo $before_title . $htmlWidget . $after_title;
    echo $after_widget;
}

wp_register_sidebar_widget(
        'analytics_chart', // your unique widget id
        'Analytics chart widget', // widget name
        'analytics_chart_widget_display', // callback function
        array(
    'description' => 'Show an analytics chart'
        )
);

function article_analytics_widget_render() {
    global $wpdb;
    if (!is_user_logged_in() ) {
       return;
    }
    wp_enqueue_style('style');
    $period = article_analytics_configuration_get_item('article_analytics_statistic_period');
    $userLogon = article_analytics_configuration_get_item('article_analytics_user_logon');
    $ownerVisits = article_analytics_configuration_get_item('article_analytics_include_owner_visit');
    $result = article_analytics_get_statistic($period, $userLogon, $ownerVisits);
    $totalVisits = 0;
    foreach ($result as $value) {
        $totalVisits += $value->nr_visits;
    }
    ?>
    <div>Article analytics<br/>
        <?php
        if ($totalVisits > 0) {
            foreach ($result as $value) {
                $content_post = get_post($value->post_id);
                ?>
                <a href="<?php echo $content_post->guid; ?>"><div class="widget-item-title"><?php echo $content_post->post_title; ?></div></a>
                <span><?php echo $value->nr_visits; ?></span>
                <div style="width: <?php echo round(150 * $value->nr_visits / $totalVisits) . 'px' ?>" class="status-bar"></div>
                <?php
            }
        } else {
            _e('No results yet', 'article_analitics');
        }
        ?>
        <div>
            <?php
        }
        ?>
