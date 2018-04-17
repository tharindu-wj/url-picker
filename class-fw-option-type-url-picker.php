<?php if (!defined('FW')) die('Forbidden');

class FW_Option_Type_Url_Picker extends FW_Option_Type
{
    public function get_type()
    {
        return 'url-picker';
    }

    /**
     * @internal
     */
    protected function _get_defaults()
    {
        return array(
            'value' => array(),
        );
    }

    public function _get_backend_width_type()
    {
        return 'full';
    }

    /**
     * @internal
     */
    protected function _enqueue_static($id, $option, $data)
    {
        // make sure to enqueue multi-picker static
        fw()->backend->option_type('multi-picker')->enqueue_static();
    }

    /**
     * @internal
     */
    protected function _render($id, $option, $data)
    {
        $wrapper_attr = array(
            'id'    => $option['attr']['id'],
            'class' => $option['attr']['class'],
        );

        return
            '<div '. fw_attr_to_html($wrapper_attr) .'>'.
            fw()->backend->option_type('multi-picker')->render(
                $id,
                array_merge($this->get_multi_picker_option(), array('value' => $option['value'])),
                $data
            ).
            '</div>'.
            /**
             * Fix side padding to display properly option full width
             * Note: This style should be enqueued in $this->_enqueue_static() but because it's very small, I included it in html
             */
            '<style type="text/css">body .fw-option-type-'. $this->get_type() .' { padding-left: 0; padding-right: 0; }</style>';
    }

    /**
     * @internal
     */
    protected function _get_value_from_input($option, $input_value)
    {
        if (is_null($input_value)) {
            $input_value = $option['value'];
        }

        return (array)$input_value;
    }


    private function get_multi_picker_option()
    {
        return array(
            'type'  => 'multi-picker',
            'picker' => array(
                'link_type' => array(
                    'label'   => __('Choose URL', 'fw'),
                    'type'    => 'select',
                    'choices' => array(
                        array( //option group 1
                            'attr' => array('label'=>'Custom'),
                            'choices'=>array(
                                'no-link'=>__('No Link','fw'),
                                'manual-link'=>__('Manual Link','fw')
                            )
                        ),
                        array( //option group 2
                            'attr'    => array('label' => __('Post Types', 'fw')),
                            'choices' => $this->get_post_types()
                        ),
                        array( //option group 3
                            'attr' => array('label'=>'Taxonomies'),
                            'choices'=> $this->get_taxonomies()
                        )
                    )
                )
            ),
            'choices' => $this->get_choices(),
            'show_borders' => false,
        );
    }


    private function get_posts($post_type)
    {
        return get_posts(
            array(
                'post_type' => $post_type,
                'post_status' => 'publish'
            ));
    }
    private function get_taxonomies()
    {
        // Gets all taxonomies
        $collector = array();
        $args = array(
            'public'   => true,
        );
        $taxonomies =get_taxonomies($args,'objects');
        foreach ($taxonomies as $key => $taxonomy) {
            $collector[$key] = empty($taxonomy->labels->name) ? $taxonomy->label : $taxonomy->labels->name;
        }
        return $collector;
    }
    public function get_post_types()
    {
        // Gets all post types
        $collector = array();
        $post_types = get_post_types(array('public' => true), 'objects');
        foreach ($post_types as $key => $post_type) {
            $have_posts = $this->get_posts($key);
            if ($key != 'attachment' && !empty($have_posts)) {
                $collector[$key] = empty($post_type->labels->name) ? $post_type->label : $post_type->labels->name;
            }
        }
        return $collector;
    }
    private function post_choices()
    {
        // Get Links from all post types and add as choices
        $post_types = get_post_types(array('public' => true));
        $collector = array();
        foreach ($post_types as $post_type) {
            $posts_collector = array();
            $posts = $this->get_posts($post_type);
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    $posts_collector[$post->ID] = empty($post->post_title) ? __('(no title)', 'fw') : $post->post_title;
                }
                $collector[$post_type] = array(
                    'link' => array(
                        'type' => 'select',
                        'attr' => array('class' => 'selectize fw-selectize'),
                        'label' => __('Select Specific posts', 'fw'),
                        'choices' => $posts_collector,
                    ),
                    'open-in'=>array( //For target='_blank'
                        'type'=>'select',
                        'label'=>'Open in:',
                        'choices'=>array(
                            'new-window'=>'New Window',
                            'same-window'=>'Same Window'
                        )
                    )
                );
            }
        }

        return $collector;
    }
    private function tax_choices()
    {
        // Get Links from all Taxonomy Terms and add as choices
        $taxonomies = get_taxonomies(array('hierarchical' => true));
        $collector = array();
        foreach ($taxonomies as $taxonomy) {
            $term_collector = array();
            $terms = get_terms($taxonomy);
            if(!empty($terms)){
                foreach ($terms as $term) {
                    $term_collector[$term->term_id]= empty($term->name)? __('(no title)','fw'): $term->name;
                }
                $collector[$taxonomy]= array(
                    'link' => array(
                        'type' => 'select',
                        'attr' => array('class' => 'selectize fw-selectize'),
                        'label' => __('Select Specific Terms', 'fw'),
                        'choices' => $term_collector,
                    ),
                    'open-in'=>array( //For target='_blank'
                        'type'=>'select',
                        'label'=>'Open in:',
                        'choices'=>array(
                            'new-window'=>'New Window',
                            'same-window'=>'Same Window'
                        )
                    )
                );
            }
        }

        return $collector;
    }
    private function get_choices()
    {
        // Merges The choices togeather with manual links
        $post_choices = $this->post_choices();
        $tax_choices = $this->tax_choices();
        $custom_choices = array(
            'no-link'=>'',
            'manual-link'=>array(
                'link'=> array(
                    'type'=> 'text', //Is there a Link or URL type???
                    'label'=>__( 'Enter URL', 'fw' )
                ),
                'open-in'=>array( //For target='_blank'
                    'type'=>'select',
                    'label'=>'Open in:',
                    'choices'=>array(
                        'new-window'=>'New Window',
                        'same-window'=>'Same Window'
                    )
                ),
            )
        );
        $merged_choices = array_merge($custom_choices,$post_choices, $tax_choices);
        return $merged_choices;
    }

}

FW_Option_Type::register('FW_Option_Type_Url_Picker');