<?php
if (!defined('ABSPATH')) exit;

define('ELWR_VERSION', '1.2.0');
define('ELWR_DIR', plugin_dir_path(__FILE__));
define('ELWR_URL', plugin_dir_url(__FILE__));

function elwr_defaults() {
    return array(
        'api_key'=>'', 'voice_id'=>'JBFqnCBsd6RMkjVDRZzb', 'model_id'=>'eleven_multilingual_v2',
        'stability'=>'0.5', 'similarity_boost'=>'0.75', 'style'=>'0', 'speaker_boost'=>'1',
        'button_text'=>'Listen to this page', 'player_enabled'=>'1', 'show_title'=>'1',
        'show_status'=>'0', 'show_speed'=>'1', 'show_skip'=>'1', 'theme_color'=>'#5b9f4d',
        'default_mode'=>'post', 'auto_generate'=>'1'
    );
}
function elwr_settings() { return wp_parse_args(get_option('elwr_settings', array()), elwr_defaults()); }
function elwr_sanitize_settings($input) {
    $old = elwr_settings(); $out = $old;
    if (isset($input['api_key']) && trim($input['api_key']) !== '') $out['api_key'] = sanitize_text_field(trim($input['api_key']));
    foreach (array('voice_id','model_id','button_text') as $key) if (isset($input[$key])) $out[$key] = sanitize_text_field($input[$key]);
    foreach (array('stability','similarity_boost','style') as $key) if (isset($input[$key])) $out[$key] = (string) max(0, min(1, (float) $input[$key]));
    $out['speaker_boost'] = !empty($input['speaker_boost']) ? '1' : '0';
    foreach (array('player_enabled','show_title','show_status','show_speed','show_skip','auto_generate') as $key) $out[$key] = !empty($input[$key]) ? '1' : '0';
    $out['theme_color'] = isset($input['theme_color']) ? sanitize_hex_color($input['theme_color']) : $old['theme_color'];
    $out['default_mode'] = (isset($input['default_mode']) && $input['default_mode'] === 'page') ? 'page' : 'post';
    return $out;
}
add_action('admin_menu', function() { add_options_page('ElevenLabs WP Reader', 'ElevenLabs Reader', 'manage_options', 'elwr-settings', 'elwr_settings_page'); });
add_action('admin_init', function() { register_setting('elwr_group', 'elwr_settings', array('sanitize_callback'=>'elwr_sanitize_settings')); });
function elwr_settings_page() {
    if (!current_user_can('manage_options')) return; $s = elwr_settings(); ?>
    <div class="wrap"><h1>ElevenLabs WP Reader</h1>
    <p>The API key stays on the WordPress server. Text-to-speech conversion is performed server-side and the frontend receives only a cached MP3 URL.</p>
    <form method="post" action="options.php"><?php settings_fields('elwr_group'); ?>
    <h2>ElevenLabs connection</h2><table class="form-table">
      <tr><th><label for="elwr_api_key">API key</label></th><td><input id="elwr_api_key" name="elwr_settings[api_key]" type="password" class="regular-text" autocomplete="new-password" placeholder="<?php echo $s['api_key'] ? esc_attr('Saved — leave blank to keep current key') : 'Paste API key'; ?>"><p class="description">Stored in WordPress options; never printed to frontend HTML or REST responses. Leave blank to keep the current key.</p></td></tr>
      <tr><th><label for="elwr_voice_id">Voice ID</label></th><td><input id="elwr_voice_id" name="elwr_settings[voice_id]" value="<?php echo esc_attr($s['voice_id']); ?>" class="regular-text"></td></tr>
      <tr><th><label for="elwr_model_id">Model ID</label></th><td><input id="elwr_model_id" name="elwr_settings[model_id]" value="<?php echo esc_attr($s['model_id']); ?>" class="regular-text"><p class="description">Example: eleven_multilingual_v2. Use a model available to your ElevenLabs account.</p></td></tr>
      <tr><th>Voice style</th><td><label>Stability <input type="number" min="0" max="1" step="0.05" name="elwr_settings[stability]" value="<?php echo esc_attr($s['stability']); ?>"></label> &nbsp; <label>Similarity boost <input type="number" min="0" max="1" step="0.05" name="elwr_settings[similarity_boost]" value="<?php echo esc_attr($s['similarity_boost']); ?>"></label> &nbsp; <label>Style exaggeration <input type="number" min="0" max="1" step="0.05" name="elwr_settings[style]" value="<?php echo esc_attr($s['style']); ?>"></label> &nbsp; <label><input type="checkbox" name="elwr_settings[speaker_boost]" value="1" <?php checked($s['speaker_boost'],'1'); ?>> Speaker boost</label></td></tr>
    </table><h2>Reading and generation</h2><table class="form-table">
      <tr><th>Reading mode</th><td><select name="elwr_settings[default_mode]"><option value="post" <?php selected($s['default_mode'],'post'); ?>>Read post content (MVP)</option><option value="page" <?php selected($s['default_mode'],'page'); ?>>Read full rendered page content (PROD)</option></select><p class="description">Full-page mode applies WordPress content filters and shortcodes before stripping HTML to readable text. Theme chrome and text rendered only by browser JavaScript are not included.</p></td></tr>
      <tr><th>Background generation</th><td><label><input type="checkbox" name="elwr_settings[auto_generate]" value="1" <?php checked($s['auto_generate'],'1'); ?>> Generate/cache MP3 when a post or page is published or updated</label><p class="description">Generation happens on the server. The first publish/update may take longer; visitors do not call ElevenLabs.</p></td></tr>
      <tr><th><label for="elwr_button_text">Button label</label></th><td><input id="elwr_button_text" name="elwr_settings[button_text]" value="<?php echo esc_attr($s['button_text']); ?>" class="regular-text"></td></tr>
    </table><h2>Display options</h2><table class="form-table">
      <?php foreach (array('player_enabled'=>'Display the audio player','show_title'=>'Show post/page title','show_status'=>'Show player status text','show_speed'=>'Show playback speed selector','show_skip'=>'Show 15-second skip controls') as $key=>$label): ?><tr><th><?php echo esc_html($label); ?></th><td><label><input type="checkbox" name="elwr_settings[<?php echo esc_attr($key); ?>]" value="1" <?php checked($s[$key],'1'); ?>> Enabled</label></td></tr><?php endforeach; ?>
      <tr><th><label for="elwr_theme_color">Player color</label></th><td><input type="color" id="elwr_theme_color" name="elwr_settings[theme_color]" value="<?php echo esc_attr($s['theme_color']); ?>"></td></tr>
    </table><?php submit_button('Save Settings'); ?></form>
    <hr><h2>Generate audio manually</h2>
    <p>If a player says audio has not been generated, choose a published post or page and generate the MP3 here. Generation runs on the server; visitors only receive the cached MP3 URL.</p>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="elwr_manual_generate">
      <?php wp_nonce_field('elwr_manual_generate'); ?>
      <label for="elwr_post_id"><strong>Published post/page ID</strong></label>
      <input id="elwr_post_id" name="post_id" type="number" min="1" required>
      <label for="elwr_mode"><strong>Audio mode</strong></label>
      <select id="elwr_mode" name="mode"><option value="post">Read post content (MVP)</option><option value="page">Read full filtered page content (PROD)</option></select>
      <?php submit_button('Generate MP3 now', 'primary', 'submit', false); ?>
    </form>
    <p class="description">To find the ID, edit the post/page and check the URL for <code>post=123</code>. Check the site's PHP error log if generation fails.</p>
    <h2>Shortcode</h2><p><code>[elevenlabs_reader]</code></p><p>Options: <code>[elevenlabs_reader mode="post" label="Listen to this article" title="1" status="0" speed="1" skip="1"]</code></p>
    <h2>Headless REST API</h2><p><code>GET /wp-json/elwr/v1/audio/POST_ID?mode=post</code> returns cached MP3 metadata as JSON. The ElevenLabs API key is never returned. A cache must exist first; publish/update the content with background generation enabled.</p>
    </div><?php
}

function elwr_register_assets() {
    wp_register_style('elwr-style', ELWR_URL.'assets/reader.css', array(), ELWR_VERSION);
    wp_register_script('elwr-script', ELWR_URL.'assets/reader.js', array(), ELWR_VERSION, true);
}
add_action('wp_enqueue_scripts', 'elwr_register_assets');
function elwr_localize_frontend_script() { wp_localize_script('elwr-script', 'ELWR', array('endpoint'=>esc_url_raw(rest_url('elwr/v1/audio')), 'errorText'=>'Audio is not ready yet. Publish/update the content or ask the site administrator to generate it.')); }
function elwr_enqueue_assets() { wp_enqueue_style('elwr-style'); wp_enqueue_script('elwr-script'); elwr_localize_frontend_script(); }
function elwr_shortcode($atts) {
    if (!is_singular()) return ''; $s=elwr_settings(); $post_id=get_queried_object_id();
    $atts=shortcode_atts(array('mode'=>$s['default_mode'],'label'=>$s['button_text'],'title'=>$s['show_title'],'status'=>$s['show_status'],'speed'=>$s['show_speed'],'skip'=>$s['show_skip']),$atts,'elevenlabs_reader');
    $mode=$atts['mode']==='page'?'page':'post'; elwr_enqueue_assets();
    if ($s['player_enabled'] !== '1') return '';
    $title = $atts['title'] !== '0' ? '<div class="elwr-title">'.esc_html(get_the_title($post_id)).'</div>' : '';
    $status = $atts['status'] !== '0' ? '<div class="elwr-status" aria-live="polite">'.esc_html__('Audio ready','elevenlabs-wp-reader').'</div>' : '<div class="elwr-status" aria-live="polite" hidden></div>';
    $skip = $atts['skip'] !== '0' ? '<button type="button" class="elwr-skip" data-seconds="-15" aria-label="Back 15 seconds">↶ 15s</button>' : '';
    $skip .= $atts['skip'] !== '0' ? '<button type="button" class="elwr-skip" data-seconds="15" aria-label="Forward 15 seconds">15s ↷</button>' : '';
    $speed = $atts['speed'] !== '0' ? '<select class="elwr-speed" aria-label="Playback speed"><option value="0.75">0.75×</option><option value="1" selected>1×</option><option value="1.25">1.25×</option><option value="1.5">1.5×</option><option value="1.75">1.75×</option><option value="2">2×</option></select>' : '';
    return '<section class="elwr-reader" style="--elwr-color:'.esc_attr($s['theme_color']).'" data-post-id="'.esc_attr($post_id).'" data-mode="'.esc_attr($mode).'" data-api="'.esc_attr(rest_url('elwr/v1/audio/'.$post_id)).'"><button type="button" class="elwr-generate" aria-label="Play article audio"><span class="elwr-play-icon" aria-hidden="true">▶</span></button><div class="elwr-player-content">'.$title.$status.'<div class="elwr-controls"><span class="elwr-current">00:00</span><input class="elwr-seek" type="range" min="0" max="100" value="0" aria-label="Audio progress"><span class="elwr-duration">00:00</span>'.$skip.$speed.'</div><audio class="elwr-audio-element" preload="none"></audio><noscript>Audio player requires JavaScript. <a href="'.esc_url(rest_url('elwr/v1/audio/'.$post_id)).'">Get audio details</a>.</noscript></div></section>';
}
add_shortcode('elevenlabs_reader','elwr_shortcode');

function elwr_extract_text($post, $mode='post') {
    $raw = $post->post_content;
    if ($mode === 'page') {
        // Render Gutenberg blocks and shortcodes on the server. Remove this reader shortcode to avoid recursion.
        $raw = preg_replace('/\[elevenlabs_reader[^\]]*\]/i', ' ', $raw);
        $raw = apply_filters('the_content', $raw);
    } else { $raw = strip_shortcodes($raw); }
    $raw = preg_replace('/<!--.*?-->/s',' ', $raw);
    $raw = wp_strip_all_tags($raw, true);
    $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
    return trim(preg_replace('/\s+/u',' ', $raw));
}
function elwr_cache_paths($post_id, $mode, $content) {
    $s=elwr_settings(); $hash=hash('sha256', $post_id.'|'.$mode.'|'.$s['voice_id'].'|'.$s['model_id'].'|'.$s['stability'].'|'.$s['similarity_boost'].'|'.$s['style'].'|'.$s['speaker_boost'].'|'.$content);
    $upload=wp_upload_dir(); $dir=trailingslashit($upload['basedir']).'elevenlabs-wp-reader';
    return array('dir'=>$dir,'path'=>trailingslashit($dir).'elwr-'.$hash.'.mp3','url'=>trailingslashit($upload['baseurl']).'elevenlabs-wp-reader/elwr-'.$hash.'.mp3','hash'=>$hash,'error'=>!empty($upload['error']));
}
function elwr_generate_for_post($post_id, $mode='post') {
    $s=elwr_settings(); if (empty($s['api_key'])) return new WP_Error('elwr_no_key','Configure the ElevenLabs API key in Settings → ElevenLabs Reader.');
    $post=get_post($post_id); if (!$post || $post->post_status!=='publish' || !in_array($post->post_type,array('post','page'),true)) return new WP_Error('elwr_invalid_post','Only published posts and pages can be read.');
    $content=elwr_extract_text($post,$mode); if ($content==='') return new WP_Error('elwr_empty_content','No readable content found.');
    // ElevenLabs request size limits vary by model/account; chunk text conservatively and store one combined MP3 stream.
    $chunks=array(); $remaining=$content; while ($remaining!=='') { if (function_exists('mb_substr')) { $piece=mb_substr($remaining,0,4500); $remaining=mb_substr($remaining,mb_strlen($piece)); } else { $piece=substr($remaining,0,4500); $remaining=substr($remaining,strlen($piece)); }
        if ($remaining!=='' && function_exists('mb_strrpos')) { $space=mb_strrpos($piece,' '); if ($space!==false && $space>1000) { $remaining=mb_substr($piece,$space+1).$remaining; $piece=mb_substr($piece,0,$space); } }
        $chunks[]=$piece;
    }
    $cache=elwr_cache_paths($post_id,$mode,$content); if ($cache['error']) return new WP_Error('elwr_upload_error','Uploads directory is unavailable.');
    if (!wp_mkdir_p($cache['dir'])) return new WP_Error('elwr_mkdir_error','Could not create audio cache directory.');
    if (!file_exists(trailingslashit($cache['dir']).'index.php')) @file_put_contents(trailingslashit($cache['dir']).'index.php',"<?php // Silence is golden.\n");
    if (file_exists($cache['path']) && filesize($cache['path'])>100) return $cache['url'];
    $all_audio='';
    foreach ($chunks as $chunk) {
        $body=array('text'=>$chunk,'model_id'=>$s['model_id'],'voice_settings'=>array('stability'=>(float)$s['stability'],'similarity_boost'=>(float)$s['similarity_boost'],'style'=>(float)$s['style'],'use_speaker_boost'=>$s['speaker_boost']==='1'));
        $response=wp_remote_post('https://api.elevenlabs.io/v1/text-to-speech/'.rawurlencode($s['voice_id']).'?output_format=mp3_44100_128',array('timeout'=>90,'headers'=>array('xi-api-key'=>$s['api_key'],'Content-Type'=>'application/json','Accept'=>'audio/mpeg'),'body'=>wp_json_encode($body)));
        if (is_wp_error($response)) return new WP_Error('elwr_api_error','Could not connect to ElevenLabs: '.$response->get_error_message());
        $status=wp_remote_retrieve_response_code($response); $audio=wp_remote_retrieve_body($response);
        if ($status<200 || $status>=300) { $msg='ElevenLabs returned HTTP '.$status.'. Check API key, voice/model IDs and account quota.'; return new WP_Error('elwr_api_error',$msg); }
        if (strlen($audio)<100) return new WP_Error('elwr_audio_error','ElevenLabs returned an empty audio response.');
        $all_audio.=$audio;
    }
    $tmp=$cache['path'].'.tmp'; if (file_put_contents($tmp,$all_audio,LOCK_EX)===false) return new WP_Error('elwr_audio_save_error','Could not save generated MP3.');
    @chmod($tmp,0644); if (!rename($tmp,$cache['path'])) { @unlink($tmp); return new WP_Error('elwr_audio_save_error','Could not finalize generated MP3.'); }
    update_post_meta($post_id,'_elwr_audio_'.$mode,$cache['hash']);
    return $cache['url'];
}
// Pre-generate on publish/update, so frontend playback only fetches an already-generated MP3.
add_action('save_post',function($post_id,$post,$update){
    if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !($post instanceof WP_Post)) return;
    if (!in_array($post->post_type,array('post','page'),true) || $post->post_status!=='publish') return;
    $s=elwr_settings(); if ($s['auto_generate']!=='1' || empty($s['api_key'])) return;
    // Run after the save request on the server. Generation does not happen in the visitor's browser.
    foreach (array_unique(array('post',$s['default_mode'])) as $mode) { $result=elwr_generate_for_post($post_id,$mode); if (is_wp_error($result)) error_log('[ELWR] '.$result->get_error_message()); }
},20,3);

add_action('rest_api_init',function(){
    register_rest_route('elwr/v1','/audio/(?P<id>\d+)',array('methods'=>'GET','callback'=>'elwr_rest_audio','permission_callback'=>'__return_true','args'=>array('id'=>array('sanitize_callback'=>'absint'))));
    register_rest_route('elwr/v1','/generate/(?P<id>\d+)',array('methods'=>'POST','callback'=>'elwr_rest_generate','permission_callback'=>function(){return current_user_can('edit_posts') && wp_verify_nonce(isset($_SERVER['HTTP_X_WP_NONCE'])?sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])):'','wp_rest');}));
});
function elwr_rest_audio(WP_REST_Request $request) {
    $id=absint($request['id']); $mode=$request->get_param('mode')==='page'?'page':'post'; $post=get_post($id);
    if (!$post || $post->post_status!=='publish' || !in_array($post->post_type,array('post','page'),true)) return new WP_Error('elwr_not_found','Published post/page not found.',array('status'=>404));
    $content=elwr_extract_text($post,$mode); $cache=elwr_cache_paths($id,$mode,$content);
    if (file_exists($cache['path']) && filesize($cache['path'])>100) return rest_ensure_response(array('ready'=>true,'post_id'=>$id,'mode'=>$mode,'audio_url'=>esc_url_raw($cache['url']),'mime_type'=>'audio/mpeg'));
    return new WP_REST_Response(array('ready'=>false,'post_id'=>$id,'mode'=>$mode,'message'=>'Audio has not been generated yet. Publish/update the post or ask an administrator to generate it.'),404);
}
function elwr_rest_generate(WP_REST_Request $request) { $id=absint($request['id']); $mode=$request->get_param('mode')==='page'?'page':'post'; $result=elwr_generate_for_post($id,$mode); if (is_wp_error($result)) return new WP_Error($result->get_error_code(),$result->get_error_message(),array('status'=>500)); return rest_ensure_response(array('ready'=>true,'post_id'=>$id,'mode'=>$mode,'audio_url'=>esc_url_raw($result),'mime_type'=>'audio/mpeg')); }

// Manual admin generation makes API and content errors visible instead of leaving the player in a not-ready state.
add_action('admin_post_elwr_manual_generate', function() {
    if (!current_user_can('manage_options')) wp_die('You are not allowed to generate audio.');
    check_admin_referer('elwr_manual_generate');
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $mode = isset($_POST['mode']) && $_POST['mode'] === 'page' ? 'page' : 'post';
    $result = elwr_generate_for_post($post_id, $mode);
    if (is_wp_error($result)) {
        $message = $result->get_error_message();
        $status = 'error';
    } else {
        $message = 'MP3 generated successfully: ' . $result;
        $status = 'success';
    }
    set_transient('elwr_admin_generation_' . get_current_user_id(), array('status'=>$status,'message'=>$message), 90);
    wp_safe_redirect(add_query_arg(array('page'=>'elwr-settings','elwr_generated'=>'1'), admin_url('options-general.php')));
    exit;
});
add_action('admin_notices', function() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'elwr-settings' || !current_user_can('manage_options')) return;
    $notice = get_transient('elwr_admin_generation_' . get_current_user_id());
    if (!$notice) return;
    delete_transient('elwr_admin_generation_' . get_current_user_id());
    $class = $notice['status'] === 'success' ? 'notice notice-success' : 'notice notice-error';
    echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($notice['message']) . '</p></div>';
});
