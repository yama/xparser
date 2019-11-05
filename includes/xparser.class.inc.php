<?php
class XPARSE {
	
	public $optionVar = array();
	public $postVar = array();
	public $extraVar = array();
	public $template_path;
	public $default_attachment_image;
	
	function __construct() {
		$this->template_path = get_template_directory() . '/';
		$this->default_attachment_image = '';
	}
	
    function parsePosts($tpl='<li>[+post_title+]</li>', $args=array()) {
        $posts = (array)$this->_get_posts_data($args);
        $content = array();
        foreach($posts as $i=>$post) {
            $content[] = xparser()->parseText($tpl, $post);
        }
        return implode("\n", $content);
    }

    function _get_posts_data($args=array()) {
        if(!isset($args['post_type'])) {
            $args['post_type'] = 'post';
        }
        if(!isset($args['posts_per_page'])) {
            $args['posts_per_page'] = 5;
        }

        $wp_query = new WP_Query( $args );
        if ( !$wp_query->have_posts() ) {
            return array();
        }

        global $post;
        $posts = array();
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
            $post->permalink = get_permalink($post->id);
            $post->post_date_format = date('Y.m.d', strtotime($post->post_date));
            $posts[] = $post;
        }
        wp_reset_postdata();
        return $posts;
    }

	function getOptionVars() {
		$var = wp_load_alloptions();
		$var['site_url']         = $var['siteurl'];
		$var['site_name']        = $var['blogname'];
		$var['site_description'] = $var['blogdescription'];
		$var['description']      = $var['blogdescription'];
		$var['charset']          = $var['blog_charset'];
		if(isset($var['mailserver_login'])) unset($var['mailserver_login']);
		if(isset($var['mailserver_pass'])) unset($var['mailserver_pass']);
        $var['site_name']           = get_bloginfo('name');
        $var['site_url']            = get_bloginfo('url');
        $var['site_description']    = get_bloginfo('description');
		$var['lang_code']           = get_bloginfo('language');
        
        $var['language_attributes'] = get_language_attributes();
        $var['charset']             = get_bloginfo('charset');
        $var['theme_url']           = get_template_directory_uri() . '/';
		return $var;
	}
	
	function getPostVars($post_id=0) {
		if(!is_singular()) return array();
		
		//setup_postdata();
		if(!$post_id) $post_id = get_the_id();
        $post = get_post($post_id);
        $var = array();
        foreach($post as $key=>$value) {
            $var[$key] = $value;
        }
        $var['ID']          = $post_id;
        $var['title']       = $post->post_title;
        $var['description'] = $this->getSummary($post->post_excerpt);
        $var['permalink']   = get_permalink($post_id);
        $var['url']         = $var['permalink'];
        $var['pict_url']    = $this->get_the_attachment_image_url($post_id);
        $var['ogp-type']    = is_front_page() ? 'website' : 'article'; // get_option('show_on_front')
        $var['body_class']  = join(' ', get_body_class());
        $var['wp_head']     = $this->get_wp_head();
        $var['wp_footer']   = $this->get_wp_footer();
        $var['id']          = $post_id;
        $var['pagetitle']   = $post->post_title;
        $var['content']     = apply_filters('the_content',$post->post_content);
        return $var;
	}
	
	function parse($content) {
		
        if(strpos($content, '<?php')!==false) {
            ob_start();
            // exit($content);
            eval('?>'.$content);
            $content = ob_get_clean();
        }
		if(strpos($content,'[(')===false && strpos($content,'[*')===false) {
            return $content;
        }
		
		$optionVars = $this->getOptionVars();
		$postVars   = $this->getPostVars();
		$limit = 10;
		$i=0;
		$bt = md5('');
		while($i<$limit) {
			if($bt==md5($content)) break;
			$i++;
			$bt = md5($content);
			if(strpos($content,'[(')!==false) $content = $this->parseText($content,$optionVars,'[(',')]');
			if(strpos($content,'[*')!==false) $content = $this->parseText($content,$postVars,  '[*','*]');
		}
		return $content;
	}
	
	function parseFile($tpl_path='') {
		$tpl_path_full = $this->template_path . $tpl_path;
		if(is_file($tpl_path_full)) {
            return  file_get_contents($tpl_path_full);
        }
		return '';
	}
	
    function parseText($tpl,$ph=array(),$left='[+',$right='+]') {
        foreach($ph as $k=>$v) {
            $k = "{$left}{$k}{$right}";
            $tpl = str_replace($k,$v,$tpl);
        }
        return $tpl;
    }
    
    function getSummary($content='') {
        mb_internal_encoding('UTF-8');
        $delim = 'ÅB';
        $limit = 120;
        $content = strip_tags($content);
        
        $content = str_replace(array("\r\n","\r","\n","\t",'&nbsp;'),' ',$content);
        if(preg_match('/\s+/',$content))
            $content = preg_replace('/\s+/',' ',$content);
        $content = trim($content);
        
        if(mb_strlen($content) < $limit) return $content;
        
        $pos = strpos($content, $delim);
        
        if($pos!==false && $pos<$limit) {
            $_ = explode($delim, $content);
            $text = '';
            foreach($_ as $v) {
                if($limit <= mb_strlen($text.$v.$delim)) break;
                $text .= $v.$delim;
            }
            if($text) $content = $text;
        }
        
        if($limit<mb_strlen($content) && mb_strpos($content,' ')!==false) {
            $_ = explode(' ', $content);
            $text = '';
            foreach($_ as $v) {
                if($limit <= mb_strlen($text.$v.' ')) break;
                $text .= $v . ' ';
            }
            if($text!=='') $content = $text;
        }
        
        if($limit < mb_strlen($content)) $content = substr($content, 0, $limit);
        if(substr($content,-1)==$delim) $content = rtrim($content,$delim) . $delim;
        
        return $content;
    }
    
    function get_wp_head() {
        wp_deregister_script('jquery');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'feed_links');
        remove_action('wp_head', 'feed_links_extra');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head','rest_output_link_wp_head');
        remove_action('wp_head', 'print_emoji_detection_script', 7 );
        remove_action('wp_print_styles', 'print_emoji_styles');
        ob_start();
        wp_head();
        return ob_get_clean();
    }
    
    function get_wp_footer() {
        ob_start();
        wp_footer();
        $rs = ob_get_clean();
        return $rs;
    }
    
    function get_the_attachment_image_url($postid=0) {
        if(!$postid) $postid = get_the_id();
        $img = wp_get_attachment_image_src(get_post_thumbnail_id($postid), 'full');
        if(!$img[0]) $img[0] = $this->default_attachment_image;
        return $img[0];
    }
}