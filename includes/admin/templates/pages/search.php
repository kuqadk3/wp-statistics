<div class="postbox-container" id="wps-big-postbox">
    <div class="metabox-holder">
        <div class="meta-box-sortables">
            <div class="postbox" id="<?php echo \WP_STATISTICS\Meta_Box::getMetaBoxKey( 'search' ); ?>">
                <button class="handlediv" type="button" aria-expanded="true">
                    <span class="screen-reader-text"><?php printf( __( 'Toggle panel: %s', 'wp-statistics' ), $title ); ?></span>
                    <span class="toggle-indicator" aria-hidden="true"></span>
                </button>
                <h2 class="hndle"><span><?php echo $title; ?></span></h2>
                <div class="inside">
                    <!-- Do Js -->
                </div>
            </div>
        </div>
    </div>
</div>