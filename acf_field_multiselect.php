<?php
add_action('init', function () {
    if (class_exists('acf_field_multiselect') || !class_exists('acf_field')) {
        return;
    }

    /**
     * Class for ACF field.
     */
    class acf_field_multiselect extends acf_field
    {

        function initialize()
        {
            parent::initialize();
            // Vars.
            $this->name = 'multiple_terms';
            $this->label = __('Multiple Terms');
            $this->category = 'choice';
            $this->defaults = array(
                'choices' => array(),
                'value' => array(),
                'tax_type' => 0,
                'allow_null' => 0,
                'ui' => 0,
                'ajax' => 1,
                'type_value' => 1,
                'multiple' => 1,
                'return_format' => 'value',
            );

            // ajax
            add_action('wp_ajax_acf/fields/select_multiple/query', array($this, 'ajax_query'));
            add_action('wp_ajax_nopriv_acf/fields/select_multiple/query', array($this, 'ajax_query'));

            // Extra.
            add_filter('acf/field_wrapper_attributes', array($this, 'wrapper_attributes'), 10, 2);

        }

        function ajax_query()
        {
            // validate
            if (!acf_verify_ajax()) {
                die();
            }

            // get choices
            $response = $this->get_ajax_query($_POST);

            // return
            acf_send_ajax_results($response);

        }


        function input_admin_enqueue_scripts()
        {

            $dir = plugin_dir_url(__FILE__);

            // register & include JS
            wp_register_script('acf-input-taxonomy-chooser', "{$dir}js/input.js");
            wp_enqueue_script('acf-input-taxonomy-chooser');

        }

        public function wrapper_attributes($wrapper, $field)
        {
            if ('multiple_terms' === $field['type']) {
                $wrapper['class'] .= ' acf-field-taxonomy';
                $wrapper['data-type'] = 'select';
            }
            return $wrapper;
        }

        /**
         *  This function will return an array of data formatted for use in a select2 AJAX response.
         */
        function get_ajax_query($options = array())
        {

            // Defaults.
            $options = acf_parse_args($options, array(
                'post_id' => 0,
                's' => '',
                'field_key' => '',
                'paged' => 0,
            ));

            // Load field.
            $field = acf_get_field($options['field_key']);

            if (!$field) {
                return false;
            }

            // Vars.
            $args = array();
            $limit = 20;
            $offset = 20 * ($options['paged'] - 1);

            // Hide Empty.
            $args['hide_empty'] = true;
            $args['number'] = $limit;
            $args['offset'] = $offset;

            // Pagination
            // Don't bother for hierarchial terms, we will need to load all terms anyway.
            if ($options['s']) {
                $args['search'] = $options['s'];
            }

            $terms = get_terms( array(
                'taxonomy' => 'package_price',
                'hide_empty' => false,
            ) );

            $new_array = array();

            foreach ($terms as $item) {
                // Vérifier si l'élément est un objet WP_Term
                if (is_a($item, 'WP_Term')) {
                    // Vérifier si la propriété 'taxonomy' existe
                    if (property_exists($item, 'taxonomy')) {
                        $parent_id = $item->parent;
                        $term_id = $item->term_id;
                        $name = $item->name;

                        // Récupérer le nom de la catégorie parent
                        $parent_name = '';
                        if ($parent_id > 0) {
                            $parent_term = get_term($parent_id);
                            if (is_a($parent_term, 'WP_Term')) {
                                $parent_name = $parent_term->name;
                            }
                        }

                        // Vérifier si le tableau contient déjà une entrée avec cette taxonomie
                        $found_taxonomy = false;
                        foreach ($new_array as &$taxonomy_item) {
                            if ($taxonomy_item['text'] === $parent_id) {
                                $found_taxonomy = true;
                                // Ajouter le terme à la liste des enfants
                                $taxonomy_item['children'][] = array(
                                    'id' => $term_id,
                                    'text' => $name,
                                    'parent' => $parent_id,
                                );
                                break;
                            }
                        }

                        // Si la taxonomie n'existe pas encore dans le tableau, l'ajouter
                        if (!$found_taxonomy) {
                            $new_array[] = array(
                                'text' => $parent_name,
                                'children' => array(
                                    array(
                                        'id' => $term_id,
                                        'text' => $name,
                                        'parent' => $parent_id,
                                    ),
                                ),
                            );
                        }
                    }
                }
            }


                // vars
            $response = array(
                'results' => $new_array,
                'limit' => $limit
            );

            // Return.
            return $response;

        }

        function load_value($value, $post_id, $field)
        {
            // Return an array when field is set for multiple.
            if ($field['multiple']) {
                if (acf_is_empty($value)) {
                    return array();
                }
                return acf_array($value);
            }

            // Otherwise, return a single value.
            return acf_unarray($value);
        }

        function update_field($field)
        {
            // decode choices (convert to array)
            $field['choices'] = acf_decode_choices($field['choices']);
            $field['default_value'] = acf_decode_choices($field['default_value'], true);

            // Convert back to string for single selects.
            if (!$field['multiple']) {
                $field['default_value'] = acf_unarray($field['default_value']);
            }

            // return
            return $field;
        }

        function update_value($value)
        {
            // Bail early if no value.
            if (empty($value)) {
                return $value;
            }

            // Format array of values.
            // - Parse each value as string for SQL LIKE queries.
            if (is_array($value)) {
                $value = array_map('strval', $value);
            }

            // return
            return $value;
        }

        /**
         *  Create the HTML interface for your field.
         */
        function render_field($field)
        {
            $show_value = get_field($field['key'], 'options');
            $terms_choices = array();

            if (!empty($show_value)) {
                foreach ($show_value as $term_id) {
                    $term = get_term($term_id);
                    if ($term) {
                        $terms_choices[$term_id] = $term->name;
                    }
                }
            }

            $field['multiple'] = 1;
            $field['value'] = (!empty($show_value)) ? $show_value : [];
            $field['choices'] = $terms_choices;

            // Vars.
            $div = array(
                'id' => $field['id'],
                'class' => $field['class'] . ' js-multi-taxonomy-select2',
                'name' => $field['name'],
                'data-ui' => $field['ui'],
                'data-ajax' => $field['ajax'],
                'data-placeholder' => $field['placeholder'],
                'data-allow_null' => $field['allow_null'],
                'data-ftype' => 'select',
            );

            ?>
            <div <?php acf_esc_attr_e($div); ?>>
                <?php $this->render_field_select($field); ?>
            </div>
            <?php
        }


        /**
         *  Create the HTML interface for your field/
         */
        function render_field_select($field)
        {
            $field['type'] = 'select';
            $field['ui'] = 1;
            $field['ajax'] = 1;

            acf_render_field($field);
        }
    }

    acf_register_field_type('acf_field_multiselect');

});