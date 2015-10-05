<?php get_header(); ?>
<div id="primary" class="site-content">
    <div id="content" role="main">
    <?php
    $sugarform_posts = array( 'post_type' => 'sugarform', );
    $loop = new WP_Query( $sugarform_posts );
    ?>
    <?php while ( have_posts() ) : the_post();?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php $sugarform_display_title = get_post_meta( $post->ID, 'sugarform_display_title', true );
                if($sugarform_display_title == 1) { ?>
                    <header class="entry-header">
                    <h2><?php the_title(); ?></h2>
                    </header>
                <?php } ?>
            <div class="entry-content">
                <?php
                $fields = json_decode(get_post_meta($post->ID, 'sugarform_mapping', true));
                $errors = array();
                $sugarform_enable_recaptcha = get_post_meta($post->ID, 'sugarform_enable_recaptcha', true);
                if(isset($_POST['sugarform_action'])) {
                    if($_POST['sugarform_action']=='sugarform_submit') {
                        if($sugarform_enable_recaptcha) {
                            require_once('lib/recaptchalib.php');
                            $privatekey = "6LdCxMkSAAAAAEiubQLAoI2_ksrxVlyTlCHntEtQ";
                            $resp = recaptcha_check_answer ($privatekey,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
                            if (!$resp->is_valid) {
                                // What happens when the CAPTCHA was entered incorrectly
                                $errors[] = "The reCAPTCHA wasn't entered correctly. Go back and try it again. (reCAPTCHA said: " . $resp->error . ")";
                            }
                        }
                        //  further validation?
                        
                        //  post data
                        $module = get_post_meta($post->ID, 'sugar_module',true);
                        sugarform_post($module,$fields,$_POST);
                        
                        if(count($errors) == 0) {
                            $sugarform_confirmation_page = get_post_meta( $post->ID, 'sugarform_confirmation_page', true );
                            if($sugarform_confirmation_page != 0) {
                                //  redirect
                                $permalink = get_permalink($sugarform_confirmation_page);
                                //echo "<script> window.location = '$permalink';</script>";
                            }
                            else { ?>
                                <p>Form submitted.</p>
                            <?php
                            }
                        }
                        
                    }
                }
                if (isset($_POST['sugarform_action']) == false || count($errors)>0) {
                    the_content();
                    //  show errors?
                    if(count($errors)>0) { ?>
                        <div style="color: red">
                        <ul>The following error(s) were encountered:
                        <?php foreach($errors as $err) {
                            echo "<li>$err</li>";
                            }
                            echo "</ul></div>";
                        }
                    ?>
            <form name="sugarform" method="POST">
                <table>
                <?php //  render fields
                $f_html = "";
                foreach($fields as $field) {
                    if($field->type=="heading") {
                        $f_html .= "<tr><td colspan='2'><b>".$field->label."<b></td></tr>";
                        continue;
                    }
                    $f_html .= "<tr><th><label for='".$field->name."'>".$field->label.":</label></th><td>";
                    $req = ($field->required == '1' ? "required" : "");
                    if(in_array($field->type,array('text','email','number','password','url','date','file'))) {
                        $size = ($field->size != '' ? "size='".$field->size."'" :"");
                        $f_html .= "<input type='".$field->type."' name='".$field->name."' $size $req value=''/></td></tr>";
                        continue;
                    }
                    if($field->type=='textarea') {
                        $cols = "cols='".($field->size != '' ? $field->size :"50")."'";
                        $f_html .="<textarea name='".$field->name."' $cols rows='5' $req></textarea></td></tr>";
                        continue;
                    }
                    if($field->type=='select' || $field->type=='multi-select') {
                        $f_html .= "<select ".($field->type=='multi-select'?"multiple":"")." name='".$field->name."' $req>";
                        foreach(explode(',',$field->available_values) as $o) {
                            $f_html .= "<option value='$o'>$o</option>";
                        }
                        $f_html .= "</select></td></tr>";
                        continue;
                    }
                    if($field->type=='radio') {
                        foreach(explode(',',$field->available_values) as $o) {
                            $f_html .=  "<input type='radio' name='".$field->name."' value='$o' >$o</input></td></tr>";
                        }
                        continue;
                    }
                    if($field->type=='checkbox') {
                        $f_html .=  "<input type='checkbox' name='".$field->name."' value='1' /></td></tr>";
                        continue;
                    }
                    if($field->type=='checkbox group') {
                        foreach(explode(',',$field->available_values) as $o) {
                            $f_html .=  "<input type='checkbox' name='".$field->name."' value='$o' />&nbsp;<label>$o</label>";
                        }
                        $f_html .= "</td></tr>";
                        continue;
                    }
                }
                echo $f_html;
                
                //  recptcha
                $sugarform_recaptcha_theme = get_post_meta($post->ID, 'sugarform_recaptcha_theme', true);
                if($sugarform_enable_recaptcha) {
                    //	insert reCAPTCHA
                    require_once('lib/recaptchalib.php'); ?>
                    <script type="text/javascript">
                        var RecaptchaOptions = { theme : '<?php echo $sugarform_recaptcha_theme; ?>' };
                    </script><?php
                    $publickey = "6LdCxMkSAAAAAF3d0RYyfGK8J_buG5RS_DmTBV3H";
                    echo "<tr><td colspan='2'>".recaptcha_get_html($publickey)."</td></tr>";
                }
                $sugarform_submit_text = get_post_meta( $post->ID, 'sugarform_submit_text', true); ?>
                <tr><td colspan='2'>
                <input type="hidden" name="sugarform_action" value="sugarform_submit"/>
                <?php $sugarform_display_reset = get_post_meta( $post->ID, 'sugarform_display_reset', true );
                    if($sugarform_display_reset ==1) {
                        $sugarform_reset_text = get_post_meta( $post->ID, 'sugarform_reset_text', true); ?>
                    <input type="reset" value="<?php echo ($sugarform_reset_text ? $sugarform_reset_text: 'Clear'); ?>"/>
                    <?php } ?>
                <input type="submit" value="<?php echo ($sugarform_submit_text ? $sugarform_submit_text: 'Submit'); ?>"/>
                </td>
                </table>
            </form>
            <?php } ?>
            </div>
        </article>
 
    <?php endwhile; ?>
    </div>
</div>
<?php wp_reset_query(); ?>
<?php get_sidebar(); ?>
<?php get_footer(); ?>