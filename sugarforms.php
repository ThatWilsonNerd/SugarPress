<?php

    //  create custom 'sugarform' post type
    function sugarforms_custom_post_types() {
        global $sugar_icon;
        register_post_type ('sugarform',array(
            'labels'=>array(
                'name'=>__('SugarForms'),
                'singular_name'=>__('SugarForm'),
                'add_new' => __('Add New'),
                'add_new_item' => __('Add New Form'),
                'edit_item' => __('Edit Form')
            ),
            'description'=>'SugarForms',
            'menu_icon'=>$sugar_icon,
            'menu_position'=>105,
            'public'=>true,
            'has_archive'=>false,
            'show_ui'=>true,
            'show_in_nav_menus' => false,
            'supports'=>array('title','editor'),
            'rewrite' => array( 'slug' => 'sugarform', 'with_front' => true ),
            'can_export'=>true
            ));
        
        //  flush rewrite rules
        //flush_rewrite_rules( $hard );
    }
    add_action('init', 'sugarforms_custom_post_types');

    //  customize columns in list
    add_filter('manage_edit-sugarform_columns','sugarform_edit_columns');
    add_action('manage_sugarform_posts_custom_column','sugarform_form_custom_columns',10,2);
    add_action('add_meta_boxes','sugarform_box');
    add_action('save_post','sugarform_save');


   //  customize data columns in the form list
    function sugarform_edit_columns($cols) {
        $cols = array(
            'cb' => '<input type="checkbox"/>',
            'title' => 'Title',
            'sugar_module' => 'Import Module',
            'sugar_record_owner' => 'Record Owner',
            'sugarform_notification_email' => 'Notification Email',
            'sugarform_confirmation_page' => 'Confirmation Page'
        );
        return $cols;
    }
    function sugarform_form_custom_columns($column, $post_id) {
        $post_meta = get_post_meta($post_id, $column, true);
        if($column == 'sugarform_confirmation_page') {
            //  translate post id to post title
            echo get_the_title($post_meta);
        }
        elseif($column == 'sugar_record_owner'){
            echo get_sugar_user_full_name($post_meta);
        }
        else {
            echo $post_meta;
        }
    }
    
    function sugarform_box() {
        add_meta_box(
            'sugarform_box',
            __('Form Options', 'myplugin_textdomain'),
            'sugarform_box_content',
            'sugarform',
            'advanced',
            'high'
        );
        add_meta_box(
            'sugarmapping_box',
            __('SugarCRM Mapping', 'myplugin_textdomain'),
            'sugarmapping_box_content',
            'sugarform',
            'normal',
            'high'
        );
        
        // Move all "advanced" metaboxes above the default editor
        add_action('edit_form_after_title', function() {
            global $post, $wp_meta_boxes;
            do_meta_boxes(get_current_screen(), 'advanced', $post);
            unset($wp_meta_boxes[get_post_type($post)]['advanced']);
        });
        
    }
    function sugarform_box_content($post) {
        wp_nonce_field(plugin_basename(__FILE__),'sugarform_box_content_nonce');
        echo "<style> #sugarform_options th { text-align:left; }";
        echo "</style>";
        echo "<div id='sugarform_options'>";
        echo "<table width='100%'>";
        //  display form name?
        $sugarform_display_title = get_post_meta( $post->ID, 'sugarform_display_title', true );
        echo "<tr><th><label for='sugarform_display_title'>Display Title?</label></th>";
        echo "<td><input type='checkbox' name='sugarform_display_title' value='1' ";
        checked(($sugarform_display_title ? $sugarform_display_title : 0),1);
        echo "/></td><th>Display in Nav Menu?</th>";
        $sugarform_display_menu = get_post_meta($post->ID,'sugarform_display_menu',true);
        echo "<td><input type='checkbox' name='sugarform_display_menu' value='1' ";
        checked(($sugarform_display_menu ? $sugarform_display_menu : 0),1);
        echo "/></td></tr>";
        //  send email notification, notification address
        $sugarform_send_email = get_post_meta( $post->ID, 'sugarform_send_email', true );
        echo "<tr><th><label for='sugarform_send_email'>Send Email Notification?</label></th>";
        echo "<td><input type='checkbox' name='sugarform_send_email' value='1' ";
        checked(($sugarform_send_email ? $sugarform_send_email : 0),1);
        echo "/><th><label for='sugarform_notification_email'>Email Address:</label></th></td>";
        $sugarform_notification_email = get_post_meta( $post->ID, 'sugarform_notification_email', true );
        echo "<td><input type='email' name='sugarform_notification_email' placeholder='me@here.com' value='$sugarform_notification_email' /></td></tr>";
        //  enable reCPTCHA? select theme if so
        $sugarform_enable_recaptcha = get_post_meta( $post->ID, 'sugarform_enable_recaptcha', true );
        echo "<tr><th><label for='sugarform_enable_recaptcha'>Enable reCAPTCHA?</label></th>";
        echo "<td><input type='checkbox' name='sugarform_enable_recaptcha' value='1' ";
        checked(($sugarform_enable_recaptcha ? $sugarform_enable_recaptcha : 0),1);
        echo "/></td>";
        $sugarform_recaptcha_theme = get_post_meta( $post->ID, 'sugarform_recaptcha_theme', true );
        echo "<th><label for='sugarform_recaptcha_theme'>reCAPTCHA Theme:</label></th>";
        echo "<td><select id='sugarform_recaptcha_theme' name='sugarform_recaptcha_theme'>";
            $recaptcha_html = "";
            $recaptcha_themes = array("red","white","blackglass","clean");
            foreach($recaptcha_themes as $recaptcha_theme) {
                $sel = ($recaptcha_theme == $sugarform_recaptcha_theme ? "selected" : "");
                $recaptcha_html .= "<option $sel>".$recaptcha_theme."</option>";
            }
        echo $recaptcha_html;
        echo "</select></td></tr>";
        //  button text (submit, display reset?, reset text)
        $sugarform_submit_text = get_post_meta( $post->ID, 'sugarform_submit_text', true);
        echo "<tr><th><label for='sugarform_submit_text'>Submit Button Text:</label></th>";
        echo "<td><input type='text' name='sugarform_submit_text' placeholder='Submit' value='$sugarform_submit_text' /></td></tr>";
        $sugarform_display_reset = get_post_meta( $post->ID, 'sugarform_display_reset', true );
        echo "<tr><th><label for='sugarform_display_reset'>Display Reset Button?</label></th>";
        echo "<td><input type='checkbox' name='sugarform_display_reset' value='1' ";
        checked(($sugarform_display_reset ? $sugarform_display_reset : 0),1);
        echo "/></td><th><label for='sugarform_reset_text'>Reset Button Text:</label></th>";
        $sugarform_reset_text = get_post_meta( $post->ID, 'sugarform_reset_text', true );
        echo "<td><input type='text' name='sugarform_reset_text' placeholder='Clear' value='$sugarform_reset_text' /></td></tr>";
        //  confirmation page
        echo "<tr><th>Confirmation Page:</th>";
        $sugarform_confirmation_page = get_post_meta( $post->ID, 'sugarform_confirmation_page', true );
        $args = array('name'=>'sugarform_confirmation_page','selected'=>$sugarform_confirmation_page,'post_status'=>'draft,publish,pending');
        echo "<td colspan='3'>";
        wp_dropdown_pages($args);
        echo "</td></tr>";
        echo "</table></div>";
    }
    
    function sugarmapping_box_content($post) {
        //  use knockout js to handle the field stuff
        $knockoutjs = plugins_url('/lib/knockout-3.2.0',__FILE__);
        $icons = plugins_url('/imgs/ui-icons.png',__FILE__);
        $html = "<style>
        #postbox-container-2 { width:1000px !important}
        .sp-icon {width: 16px; height: 16px; background-image: url('$icons');display:inline-block;}
        .sp-icon-up { background-position: 0 -48px; }
        .sp-icon-down { background-position: -64px -48px; }
        .sp-icon-trash { background-position: -176px -96px; }
        </style>";
        $html .= "<script src='$knockoutjs'></script>";
        $html .= "<table width='100%' style='text-align:left;' ><tr><th>Module:</th>";
        $sugar_module = get_post_meta( $post->ID, 'sugar_module', true);
        $html .= "<td><select data-bind='options: modules, optionsText: \"module\", value: selectedModule'></select>
        <input type='hidden' name='sugar_module' data-bind='value: selectedModule().module'</td>";
        $html .= "<th>Record Owner:</th>";
        $sugar_record_owner = get_post_meta( $post->ID, 'sugar_record_owner', true);
        $html .= "<td>".user_dropdown('sugar_record_owner',$sugar_record_owner)."</td></tr>";
        $html .= "<tr><th>Total Fields:</th><td><span data-bind='text: fields().length'></span></td>";
        $html .="<th></th><td><button class='button button-small' data-bind='click: addField'>Add Field</button></td></tr>";
        $html .= "</table><hr/>
        <table>
        <thead>
            <th></th>
            <th>Name</th>
            <th>Label</th>
            <th>Type</th>
            <th>Size</th>
            <th>Target Field</th>
            <th>Available Values</th>
            <th>Default Value</th>
            <th>Required</th>
            <th>Hidden</th>
            <th>Action</th>
        </thead>
        <tbody data-bind='foreach: fields'>
            <tr>
                <td style='text-align:center;' data-bind='text: order'></td>
                <td><input type='text' size='10' data-bind='value: name' placeholder='fieldname'></input></td>
                <td><input type='text' size='10' data-bind='value: label' placeholder='Form Label'></input></td>
                <td><select style='width:120px;' data-bind='options: \$parent.fieldTypes, value: type'></select</td>
                <td><input type='number' min='0' data-bind='value: size' style='width:50px'></input></td>
                <td><select style='width:120px;' data-bind='options: \$parent.moduleFields(),value: target_field'></select></td>
                <td><input type='text' size='10' data-bind='value: available_values'></input></td>
                <td><input type='text' size='10' data-bind='value: default_value'></input></td>
                <td style='text-align:center;'><input type='checkbox' data-bind='checked: required'></input></td>
                <td style='text-align:center;'><input type='checkbox' data-bind='checked: hidden'></input></td>
                <td style='display:block'>
                    <div class='sp-icon sp-icon-up' data-bind='click: \$parent.moveFieldUp'></div>
                    <div class='sp-icon sp-icon-down' data-bind='click: \$parent.moveFieldDown'></div>
                    <div class='sp-icon sp-icon-trash' data-bind='click: \$parent.removeField'></div>
                </td>
            </tr>
        </tbody>
        </table>";
        //  add hidden field to store fields
        $html .= "<input type='hidden' name='sugarform_mapping' data-bind='value: fieldText()'></input>";
        $sugarform_mapping = get_post_meta( $post->ID, 'sugarform_mapping', true);
        //$html .= "<textarea style='width:100%' rows='6' data-bind='text: fieldText()'></textarea>";
        $html .= "<script>var spfields = ".($sugarform_mapping!='' ? $sugarform_mapping:"null").";
        var module_metadata = ".module_metadata()."
        function SugarField(f) {
            self = this;
            self.order = ko.observable(f.order);
            self.name = ko.observable(f.name);
            self.label = ko.observable(f.label);
            self.type = ko.observable(f.type);
            self.size = ko.observable(f.size);
            self.target_field = ko.observable(f.target_field);
            self.available_values = ko.observable(f.available_values);
            self.required = ko.observable(f.required);
            self.hidden = ko.observable(f.hidden);
            self.default_value = ko.observable(f.default_value);
        }
        
        function SugarFieldViewModel() {
            var self = this;
            self.modules = ko.observableArray([]);
            self.selectedModule = ko.observable();
            self.moduleFields = ko.computed(function() {return (self.selectedModule() ? self.selectedModule().fields.fields : {});},self);
            for(var prop in module_metadata) {m={module:prop,fields:module_metadata[prop]};self.modules.push(m);if(prop=='".$sugar_module."') {self.selectedModule(m);}}
            self.fieldTypes = ko.observableArray(['text','email','number','select','multi-select','radio','checkbox','checkbox group','date','textarea','heading','file','password','url']);
            self.fields = ko.observableArray();
            self.fieldText = ko.computed(function() {var v = new Array();
                ko.utils.arrayForEach(self.fields(),function(f) {v.push({order:f.order(),name:f.name(),label:f.label(),
                    type:f.type(),size:f.size(),target_field:f.target_field(),available_values:f.available_values(),required:f.required(),hidden:f.hidden(),default_value:f.default_value()});});
                return JSON.stringify(v);
            },self);
            self.addField = function() {self.fields.push(new SugarField({order:self.fields().length,name:null,label:null,type:'text',size:20,target_field:null,available_values:null,required:false,hidden:false,default_value:null}));}
            self.removeField = function(d,e) {self.fields.remove(d);self.reorderFields();};
            arrayShift = function(f,p) {var i = self.fields.indexOf(f);var n = i+p;self.fields.splice(i,1);self.fields.splice(n,0,f);self.reorderFields();};
            self.moveFieldUp = function(d,e) {if(d.order()==0){return;}else{arrayShift(d,-1);};};
            self.moveFieldDown = function(d,e) {if(d.order()==(self.fields().length-1)){return;}else{arrayShift(d,1);}};
            self.reorderFields = function() {var i =0; ko.utils.arrayForEach(self.fields(),function(f) { f.order(i);i++;});}
            if(spfields!= null) { ko.utils.arrayForEach(spfields,function(field) { self.fields.push(new SugarField(field));});}
        };";
        $html .= "ko.applyBindings(new SugarFieldViewModel());  </script>";
        echo $html;
        //echo module_metadata();
    }
    
    function sugarform_save($post_id) {
        if(isset($_POST['post_type'])) {
            if($_POST['post_type'] != 'sugarform') { return; }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
            if ( !wp_verify_nonce( $_POST['sugarform_box_content_nonce'], plugin_basename( __FILE__ ) ) ) { return; }
            if ( 'page' == $_POST['post_type'] ) { if ( !current_user_can( 'edit_page', $post_id ) ) { return; } }
                else { if ( !current_user_can( 'edit_post', $post_id ) ) { return; } }
            $sugarform_display_title = $_POST['sugarform_display_title'];
            update_post_meta( $post_id, 'sugarform_display_title', $sugarform_display_title );
            $sugarform_display_menu = $_POST['sugarform_display_menu'];
            update_post_meta( $post_id, 'sugarform_display_menu', $sugarform_display_menu );
            $sugarform_send_email = $_POST['sugarform_send_email'];
            update_post_meta( $post_id, 'sugarform_send_email', $sugarform_send_email );
            $sugarform_notification_email = $_POST['sugarform_notification_email'];
            update_post_meta( $post_id, 'sugarform_notification_email', $sugarform_notification_email );
            $sugarform_enable_recaptcha = $_POST['sugarform_enable_recaptcha'];
            update_post_meta( $post_id, 'sugarform_enable_recaptcha', $sugarform_enable_recaptcha );
            $sugarform_recaptcha_theme = $_POST['sugarform_recaptcha_theme'];
            update_post_meta( $post_id, 'sugarform_recaptcha_theme', $sugarform_recaptcha_theme );
            $sugarform_submit_text = $_POST['sugarform_submit_text'];
            update_post_meta( $post_id, 'sugarform_submit_text', $sugarform_submit_text );
            $sugarform_display_reset = $_POST['sugarform_display_reset'];
            update_post_meta( $post_id, 'sugarform_display_reset', $sugarform_display_reset );
            $sugarform_reset_text = $_POST['sugarform_reset_text'];
            update_post_meta( $post_id, 'sugarform_reset_text', $sugarform_reset_text );
            $sugarform_confirmation_page = $_POST['sugarform_confirmation_page'];
            update_post_meta( $post_id, 'sugarform_confirmation_page', $sugarform_confirmation_page );
            $sugar_module = $_POST['sugar_module'];
            update_post_meta( $post_id, 'sugar_module', $sugar_module );
            $sugar_record_owner = $_POST['sugar_record_owner'];
            update_post_meta( $post_id, 'sugar_record_owner', $sugar_record_owner);
            $sugarform_mapping = $_POST['sugarform_mapping'];
            update_post_meta( $post_id, 'sugarform_mapping', $sugarform_mapping );
        }
    }

    //  custom template
    function sugarform_template( $template_path ) {
        if ( get_post_type() == 'sugarform' ) {
            if ( is_single() ) {
                $template_path = plugin_dir_path( __FILE__ ) . 'single-sugarform.php';
            }
        }
        return $template_path;
    }
    add_filter('template_include','sugarform_template',1);
    
?>