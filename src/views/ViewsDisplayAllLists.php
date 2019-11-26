<?php

namespace mywishlist\views;

class ViewsDisplayAllLists
{
    public static function displayAllLists(){
        foreach ($list as $key => $value) {
            print $key . ': <br>';
            print '  no: ' . $value['no'] . '<br>' .
                ' user_id: ' . $value['titre'] . '<br>' .
                ' description: ' . $value['description'] . '<br>' .
                ' expiration: ' . $value['expiration'] . '<br>' .
                ' token: ' . $value['token'] . '<br>';
            print  '<br>';
        }
    }
}