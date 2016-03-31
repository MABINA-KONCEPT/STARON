<?php
/**
 * Abstract field to select an object: post, user, taxonomy, etc.
 */
abstract class MASHSB_RWMB_Object_Choice_Field extends MASHSB_RWMB_Field
{
	/**
	 * Get field HTML
	 *
	 * @param mixed $meta
	 * @param array $field
	 *
	 * @return string
	 */
	static function html( $meta, $field )
	{
		$field_class = RW_Meta_Box::get_class_name( $field );
		$meta        = (array) $meta;
		$options     = call_user_func( array( $field_class, 'get_options' ), $field );
		$output      = '';
		switch ( $field['field_type'] )
		{
			case 'checkbox_list':
			case 'radio_list':
				$output .= call_user_func( array( $field_class, 'render_list' ), $options, $meta, $field );
				break;
			case 'select_tree':
				$output .= call_user_func( array( $field_class, 'render_select_tree' ), $options, $meta, $field );
				break;
			case 'select_advanced':
			case 'select':
			default:
				$output .= call_user_func( array( $field_class, 'render_select' ), $options, $meta, $field );
				break;
		}
		return $output;
	}

	/**
	 * Normalize parameters for field
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	static function normalize( $field )
	{
		$field = parent::normalize( $field );
		$field = wp_parse_args( $field, array(
			'flatten'    => true,
			'query_args' => array(),
			'field_type' => 'select',
		) );

		if ( 'checkbox_tree' === $field['field_type'] )
		{
			$field['field_type'] = 'checkbox_list';
			$field['flatten']    = false;
		}

		switch ( $field['field_type'] )
		{
			case 'checkbox_list':
			case 'radio_list':
				$field = wp_parse_args( $field, array(
					'collapse' => true
				) );
				$field['flatten']  = 'radio_list' === $field['field_type'] ? true : $field['flatten'];
				$field['multiple'] = 'radio_list' === $field['field_type'] ? false : true;
				$field             = MASHSB_RWMB_Input_Field::normalize( $field );
				break;
			case 'select_advanced':
				$field            = MASHSB_RWMB_Select_Advanced_Field::normalize( $field );
				$field['flatten'] = true;
				break;
			case 'select_tree':
				$field             = MASHSB_RWMB_Select_Field::normalize( $field );
				$field['multiple'] = true;
				break;
			case 'select':
			default:
				$field = MASHSB_RWMB_Select_Field::normalize( $field );
				break;
		}

		return $field;
	}

	/**
	 * Get the attributes for a field
	 *
	 * @param array $field
	 * @param mixed $value
	 *
	 * @return array
	 */
	static function get_attributes( $field, $value = null )
	{
		switch ( $field['field_type'] )
		{
			case 'checkbox_list':
			case 'radio_list':
				$attributes           = MASHSB_RWMB_Input_Field::get_attributes( $field, $value );
				$attributes['class'] .= " rwmb-choice";
				$attributes['id']     = false;
				$attributes['type']   = 'radio_list' === $field['field_type'] ? 'radio' : 'checkbox';
				$attributes['name'] .= ! $field['clone'] && $field['multiple'] ? '[]' : '';
				break;
			case 'select_advanced':
				$attributes           = MASHSB_RWMB_Select_Advanced_Field::get_attributes( $field, $value );
				$attributes['class'] .= " rwmb-select_advanced";
				break;
			case 'select_tree':
				$attributes             = MASHSB_RWMB_Select_Field::get_attributes( $field, $value );
				$attributes['multiple'] = false;
				$attributes['id']       = false;
				$attributes['class'] .= " rwmb-select";
				break;
			case 'select':
			default:
				$attributes           = MASHSB_RWMB_Select_Field::get_attributes( $field, $value );
				$attributes['class'] .= " rwmb-select";
				break;
		}



		return $attributes;
	}

	/**
	 * Get field names of object to be used by walker
	 *
	 * @return array
	 */
	static function get_db_fields()
	{
		return array(
			'parent' => '',
			'id'     => '',
			'label'  => '',
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @return void
	 */
	static function admin_enqueue_scripts()
	{
		wp_enqueue_style( 'rwmb-object-choice', MASHSB_RWMB_CSS_URL . 'object-choice.css', array(), MASHSB_RWMB_VER );
		wp_enqueue_script( 'rwmb-object-choice', MASHSB_RWMB_JS_URL . 'object-choice.js', array(), MASHSB_RWMB_VER, true );
		MASHSB_RWMB_Select_Field::admin_enqueue_scripts();
		MASHSB_RWMB_Select_Advanced_Field::admin_enqueue_scripts();
	}

	/**
	 * Render checkbox_list or radio_list using walker
	 *
	 * @param $options
	 * @param $meta
	 * @param $field
	 *
	 * @return array
	 */
	static function render_list( $options, $meta, $field )
	{
		$field_class = RW_Meta_Box::get_class_name( $field );
		$db_fields   = call_user_func( array( $field_class, 'get_db_fields' ), $field );
		$walker      = new MASHSB_RWMB_Choice_List_Walker( $db_fields, $field, $meta );

		$output = sprintf( '<ul class="rwmb-choice-list %s">', $field['collapse'] ? 'collapse' : '' );

		$output .= $walker->walk( $options, $field['flatten'] ? - 1 : 0 );
		$output .= '</ul>';
		return $output;
	}

	/**
	 * Render select or select_advanced using walker
	 *
	 * @param $options
	 * @param $meta
	 * @param $field
	 *
	 * @return array
	 */
	static function render_select( $options, $meta, $field )
	{
		$field_class = RW_Meta_Box::get_class_name( $field );
		$attributes  = call_user_func( array( $field_class, 'get_attributes' ), $field, $meta );
		$db_fields   = call_user_func( array( $field_class, 'get_db_fields' ), $field );
		$walker      = new MASHSB_RWMB_Select_Walker( $db_fields, $field, $meta );

		$output = sprintf(
			'<select %s>',
			self::render_attributes( $attributes )
		);
		if ( false === $field['multiple'] )
		{
			$output .= isset( $field['placeholder'] ) ? "<option value=''>{$field['placeholder']}</option>" : '<option></option>';
		}
		$output .= $walker->walk( $options, $field['flatten'] ? - 1 : 0 );
		$output .= '</select>';
		return $output;
	}

	/**
	 * Render select_tree
	 *
	 * @param $options
	 * @param $meta
	 * @param $field
	 *
	 * @return array
	 */
	static function render_select_tree( $options, $meta, $field )
	{
		$field_class = RW_Meta_Box::get_class_name( $field );
		$db_fields   = call_user_func( array( $field_class, 'get_db_fields' ), $field );
		$walker      = new MASHSB_RWMB_Select_Tree_Walker( $db_fields, $field, $meta );
		$output      = $walker->walk( $options );

		return $output;
	}

	/**
	 * Get options for walker
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	static function get_options( $field )
	{
		return array();
	}
}
