<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/5/17
 * Time: 2:48 PM
 */

namespace Integration\Ecwid;

use Integration\Common\Event;
use Integration\Common\Postgres;
use Exception;

class SaveEstisSettings extends Event{


    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        //validate $_POST params
        $local_post = $this->validatePostQueryParams($_POST);

        //die if we have empty list
        if (empty($local_post['estis_list'])) {
            echo(0);
            die();
        }

        //update data in Ecwid db
        $this->updateAllEcwidToDb($local_post);
        echo(1);
    }

    /**
     * @param $post_data
     * if not valid die
     * @return array $post_data
     */
    private function validatePostQueryParams($post_data)
    {
        if (empty($post_data['post_data'])) {
            $this->ajaxMes('No post data');
        };


        try{
	        $post_data = json_decode($post_data['post_data'], 1);
        } catch ( Exception $ex){
        	$this->ajaxEx($ex);
        }


        $post_data['id'] *= 1;
        if (empty($post_data['id'])) {
            $this->ajaxMes('Empty $_POST[\'id\'] in SaveEstisSettings');
        }

        if (empty($post_data['estis_list'])) {
            $this->ajaxMes('Empty $_POST[\'estis_list\']');
        }

        $post_data['estis_list'] = $post_data['estis_list'] * 1;

        return $post_data;
    }

    /**
     * @param $local_post
     * add a list_id and double_opt_in to user row db
     */
    private function updateAllEcwidToDb($local_post)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("UPDATE estis_ecwid_app SET list_id = $1, double_opt_in = $2, storefront=$3",
                array($local_post['estis_list'], $local_post['double_opt_in'], $local_post['store_front_enable']));
        } catch (Exception $ex) {
            $this->ajaxEx($ex);
        }
    }
}