<?php

namespace Controller;

/**
 * Main controller class
 */
class Testimonials {
    /**
     * primary API function
     *
     * @throws \Exception
     */
    static function testimonials(){
		switch($_SERVER['REQUEST_METHOD']){
			//endpoints for getting the list or a single item
			default:
			case 'GET':
				// missing ID, it's a list
				if (empty($_GET['id'])) {
					//validate params
					\Lib\Param::validateList([
						'page' => [
							\Lib\Param::PARAM_TYPE => \Lib\Param::TYPE_NUMBER,
							\Lib\Param::TYPE_OPTION_MIN => 1,
							\Lib\Param::PARAM_THROW_EXCEPTION => true,
							\Lib\Param::PARAM_DEFAULT => 1
						],
						'limit' => [
							\Lib\Param::PARAM_TYPE => \Lib\Param::TYPE_NUMBER,
							\Lib\Param::TYPE_OPTION_MIN => 1,
							\Lib\Param::PARAM_THROW_EXCEPTION => true,
							\Lib\Param::PARAM_DEFAULT => DEFAULT_ITEMS_PER_PAGE
						],
                        'search' => [
                            \Lib\Param::PARAM_TYPE => \Lib\Param::TYPE_STRING,
                        ]

					]);
                    $search = \Lib\Param::get('search', \Lib\Param::GET_AS_SANITIZED);

					//set up the fetch criteria
					$criteria = [
						'pagination' => \Lib\Data::set('pagination', (new \Lib\Pagination())
							->set_items_per_page(\Lib\Param::get('limit', \Lib\Param::GET_AS_NUMBER))
							->set_base_url('testimonials')
							->set_from_page(\Lib\Param::get('page', \Lib\Param::GET_AS_NUMBER))
						),
					];
                    if (!empty($search)){
                        $criteria['conditions']['search'] = $search;
                    }
					//returning the list
					return array(
                        'testimonials' => \Model\Testimonials::list($criteria),
                        'pagination' => \Lib\Data::get('pagination')->get_pagination_values(),
                    );
				}else{
					//validate the ID param
					\Lib\Param::validateList([
						'id' => [
							\Lib\Param::PARAM_TYPE => \Lib\Param::TYPE_MD5,
							\Lib\Param::PARAM_THROW_EXCEPTION => true,
						]
					]);
					//get the sanitized ID
					$id = \Lib\Param::get('id', \Lib\Param::GET_AS_IS);
					if (empty($id)){
						throw new \Exception("Invalid ID");
					}
					//get the single testimonial
					$testimonial = \Model\Testimonials::get($id);
					if (empty($testimonial)){
						throw new \Exception("Invalid testimonial");
					}
					// return the testimonial
					return $testimonial;
				}

			break;
            //add new testimonial
			case 'POST':
                $validationList = array_map(
                    fn($field) => [\Lib\Param::PARAM_TYPE => $field, \Lib\Param::PARAM_THROW_EXCEPTION => true],
                    [
                        'name' => \Lib\Param::TYPE_STRING,
                        'age' => \Lib\Param::TYPE_NUMBER,
                        'location' => \Lib\Param::TYPE_STRING,
                        'imageUrl' => \Lib\Param::TYPE_STRING,
                        'comments' => \Lib\Param::TYPE_TEXT,
                    ]
                );

				\Lib\Param::validateList($validationList);

                try {
                    $id = \Model\Testimonials::add([
                        'name' => \Lib\Param::get('name', \Lib\Param::GET_AS_HTML_SAFE),
                        'age' => \Lib\Param::get('age', \Lib\Param::GET_AS_NUMBER),
                        'location' => \Lib\Param::get('location', \Lib\Param::GET_AS_HTML_SAFE),
                        'imageUrl' => \Lib\Param::get('imageUrl', \Lib\Param::GET_AS_SANITIZED),
                        'comments' => \Lib\Param::get('comments', \Lib\Param::GET_AS_SANITIZED),
                    ]);
                    \Lib\Alerts::add_message("Testimonials added, ID=$id.");
                }catch(\Exception $e){
                    if ($e->getCode() == 23000){
                        throw new \Exception('Duplicate entry!', 406);
                    }else{
                        throw $e;
                    }
                }
				//return the newly created testimonial

				return \Model\Testimonials::get($id);
			break;

		}
	}

    static function init(){
        \Model\Testimonials::init(DATA_FILE);
    }

    private static function fields(){
        return	[
            'testimonial_name' => \Lib\Param::TYPE_STRING,
            'style' => \Lib\Param::TYPE_STRING,
            'brand' => \Lib\Param::TYPE_STRING,
            'url' => \Lib\Param::TYPE_STRING,
            'testimonial_type' => \Lib\Param::TYPE_STRING,
            'shipping_price' => \Lib\Param::TYPE_NUMBER,
            'description' => \Lib\Param::TYPE_TEXT,
            'note' => \Lib\Param::TYPE_TEXT,
        ];
    }
}