<h2><?php echo __('Popcustoms Integration for WooCommerce', 'popcustoms'); ?></h2>

<div class="wrap">
    <div id="icon-options-general" class="icon32"></div>

    <div id="poststuff">
        <?php
           foreach ($errors as $error) {
               if (!$error) {
                   continue;
               }
               printf('<div class="notice notice-error inline"><p>%s</p></div>', $error);
           }
       ?>
        <div id="post-body" class="metabox-holder columns-2">
            <!-- main content -->
            <div id="post-body-content">
                <div class="meta-box-sortables ui-sortable">
                    <div class="postbox">
                        <?php if ($is_connected):?>
                            <h2><?php echo __('Your site is connected to popcustoms already.', 'popcustoms') ?></h2>
                        <?php else: ?>
                            <h3>
                             <a href="https://<?php echo $domain; ?>/login?register=1"><?php echo __('Register at Popcustoms', 'popcustoms') ?></a>
                            </h3>
                            <h2><a href="<?php echo $connect_url; ?>" class="button button-primary" target="_blank"><?php echo __('Connect to Popcustoms', 'popcustoms') ?></a></h2>
                        <?php endif; ?>

                        <div class="inside">
                            <ul>
                                <li style="color:red">1 <?php printf(__('Please contact your hosting provider and make sure our server ip %s is in the whitelist of Mod_Security or other server security softwares.', 'popcustoms'), $domain === 'popcustoms.com' ? '47.89.194.2' : '120.76.216.168'); ?></li>
                                <li>2 <?php echo __('To enable/disable Popcustoms shipping for your store, go to', 'popcustoms') ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=popcustoms_shipping' ) ); ?>"><?php echo __('WooCommerce Shipping settings', 'popcustoms') ?></a></li>
                                <li>3 <?php echo __('This plugin supports Design-plus products which allows customers make personalized designs at your store.', 'popcustoms') ?></li>
                            </ul>
                        </div>
                        <!-- .inside -->
                    </div>
                    <!-- .postbox -->
                </div>
                <!-- .meta-box-sortables .ui-sortable -->
            </div>
            <!-- post-body-content -->

            <!-- sidebar -->
            <div id="postbox-container-1" class="postbox-container">

                <div class="meta-box-sortables">

                    <div class="postbox">

                        <h2><span><?php echo __('Latest News', 'popcustoms') ?></span><span class="spinner is-active" style="float:left;margin-top:1px;display:none"></span></h2>
                        <div class="inside" id="latest-news" >
                        </div>
                    </div>
                    <!-- .postbox -->

                </div>
                <!-- .meta-box-sortables -->

            </div>
            <!-- #postbox-container-1 .postbox-container -->

        </div>
        <!-- #post-body .metabox-holder .columns-2 -->

        <br class="clear">
    </div>
    <!-- #poststuff -->
    <div id="announcement-dialog" style="display:none;">
    </div>

    <a href="#TB_inline?&width=600&height=600&inlineId=announcement-dialog" id="view-announcement-dialog" class="thickbox" style="display:none">view</a>

</div> <!-- .wrap -->
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        const section = $('#latest-news')
        $.get('https://i.<?php echo $domain; ?>/api/v1/announcements?page=1&limit=5').then(function (result) {
            if (!result.data || result.data.length === 0) {
                return
            }
            let html = ''
            for (let an of result.data) {

                html += '<p style="cursor:pointer;color: #2271b1;text-decoration:underline" class="news" data-id="'+an.id+'">'+an.title+'</p>'
            }
            section.html(html)
        })

        $('#latest-news').on("click", "p.news", function() {
            let news_id = $(this).data('id')
            $('.spinner').show()
            $.get('https://i.<?php echo $domain; ?>/api/v1/announcements/'+news_id).then(function (result) {
                if (!result.data || !result.data.title || !result.data.content) {
                    return
                }
                $('.spinner').hide()
                $('#announcement-dialog').html(result.data.content)
                $('#view-announcement-dialog').click()

            })
        });
    });
</script>
