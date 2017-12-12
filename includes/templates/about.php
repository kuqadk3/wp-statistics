<div class="wrap about-wrap full-width-layout">
    <div class="wp-statistics-about">
        <h1><?php printf( __( 'Welcome to WP-Statistics&nbsp;%s', 'wp-statistics' ), WP_Statistics::$reg['version'] ); ?></h1>
        <div class="notice notice-success is-dismissible"><p>Real-Time stats available!
                <a href="admin.php?page=wp_statistics_realtime_stats">Click here</a> to show it.</p>
            <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>

        <p class="about-text"><?php _e( 'Thank you for updating to the latest version!', 'wp-statistics' ); ?></p>
        <div class="wp-badge"><?php printf( __( 'Version %s', 'wp-statistics' ), WP_Statistics::$reg['version'] ); ?></div>

        <h2 class="nav-tab-wrapper wp-clearfix">
            <a href="#" class="nav-tab nav-tab-active" data-tab="whats-news">What’s New</a>
            <a href="#" class="nav-tab" data-tab="credit">Credits</a>
            <a href="#" class="nav-tab" data-tab="changelog">Changelog</a>
        </h2>

        <div data-content="whats-news" class="tab-content current">
            <p>Whats news</p>
        </div>

        <div data-content="credit" class="tab-content">
            <div class="about-wrap-content">
                <p class="about-description">WP-Statistics is created by some peoples and is one of
                    <a target="_blank" href="http://veronalabs.com">VeronaLabs.com</a> projects.</p>
                <h3 class="wp-people-group">Project Leaders</h3>
                <ul class="wp-people-group ">
                    <li class="wp-person">
                        <a href="http://mostafa-soufi.ir/" class="web"><?php echo get_avatar( 'mst404@gmail.com', 62, '', '', array( 'class' => 'gravatar' ) ); ?>
                            Mostafa Soufi</a>
                        <span class="title">Original Author</span>
                    </li>
                </ul>
                <h3 class="wp-people-group">Other Contributors</h3>
                <ul class="wp-people-group">
                    <li class="wp-person">
                        <a href="http://toolstack.com/" class="web"><?php echo get_avatar( 'greg@toolstack.com', 62, '', '', array( 'class' => 'gravatar' ) ); ?>
                            Greg Ross</a>
                        <span class="title">Core contributor</span>
                    </li>
                    <li class="wp-person">
                        <a href="https://dedidata.com/" class="web"><?php echo get_avatar( 'farhad0@gmail.com', 62, '', '', array( 'class' => 'gravatar' ) ); ?>
                            DediData</a>
                        <span class="title">Core Contributor</span>
                    </li>
                </ul>

                <p class="clear">WP-Statistics is being developed on GitHub, If you’re interested in contributing to
                    plugin, Please look at <a target="_blank" href="https://github.com/wp-statistics/wp-statistics">Github
                        page</a>.</p>

                <h3 class="wp-people-group">External Libraries</h3>
                <p class="wp-credits-list"><a href="http://www.maxmind.com/">MaxMind</a>,
                    <a href="https://browscap.org/">Browscap</a>, <a href="http://www.chartjs.org/">Chart.js</a>.</p>
            </div>
        </div>

        <div data-content="changelog" class="one-col tab-content">
            <p>Changelog</p>
            <ul>
                <li>- We're sorry about last issues. Now you can update to new version to resolve the problems.</li>
                <li>- Updated: Composer libraries.</li>
                <li>- Fixed: A minor bug in `get_referrer_link`.</li>
                <li>- Improvement: `wp_doing_cron` function, Check before call if is not exist.</li>
                <li>- Fixed: Issue to get IP in Hits class.</li>
                <li>- Fixed: Issue to get prefix table in searched phrases postbox.</li>
                <li>- Fixed: Issue in Browscap, Used the original Browscap library in the plugin.</li>
                <li>- If you have any problem, don't forget to send the report to your web site's [contact
                    form](https://wp-statistics.com/contact/).
                </li>
            </ul>
        </div>

        <hr>

        <div class="return-to-dashboard">
            <a href="<?php echo admin_url( 'admin.php?page=wps_overview_page' ); ?>">Go to Stats → Overview</a>
        </div>
    </div>
</div>
