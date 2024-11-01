<?php
require ('simple_html_dom.php');

class Yahoo
{
    var $nick;
    var $pass;
    var $status;
    var $logged_in = false;
    var $message_url; //
    var $login_url;
    var $auth_url;
    var $curent_page_content;
    var $server = "http://wap.yahoo.com";
    var $postdata = array();
    var $friends;
    function Yahoo($nick, $pass, $re_login = false, $status = "")
    {
        global $http;
        $http = new Http;
        $this->nick = $nick;
        $this->pass = $pass;
        $this->status = $status;
        $this->re_login = $re_login;
        $this->login();
    }
    function login()
    {
        if (!$this->is_logged_in() || $this->re_login) { //
            if ($this->re_login)
                $this->logout();
            $this->get_login_url();
            $this->get_auth_url();
            $this->auth();
        } else {
            $this->get_message_url();
        }
        $this->get_friend_list();
    }
    function logout()
    {
        global $http;
        $html = str_get_html($this->curent_page_content);
        foreach ($html->find('a') as $element) {
            if (strstr($element->href, '/w/login/logout')) {
                $logout_url = $element->href;
                break;
            }
        }
        $http->request($logout_url);
    }
    function logedin_to_messenger()
    {
        $html = str_get_html($this->curent_page_content);
        foreach ($html->find('form') as $element) {
            if (strstr($element->action, '/w/bp-messenger/checkin')) {
                $this->login_to_messenger_url = $this->server . urldecode($element->action);
                return false;
            }
        }
        return true;
    }
    function get_friend_list()
    {
        global $http;
        if ($this->friends)
            return $this->friends;
        $message_page = $http->request($this->message_url);
        $this->curent_page_content = $message_page;
        if (!$this->logedin_to_messenger()) {
            $get = $this->buid_post_string('', false);
            $url = $this->login_to_messenger_url . '/?' . $get;
            $message_page = $http->request($url);
            $this->curent_page_content = $message_page;
        }

        $html = str_get_html($message_page);
        //echo $html;
        $friends=array();
        $friend=array();
        //echo $html;die;
        foreach ($html->find('div[class=l e],div[class=l j e],div[class=f e ]') as $element) {
            if(!$element->find('a')) continue;
            $friend_url=$element->find('a',0)->href;
            if (substr($friend_url, 0, 7) != 'http://') {
                $friend_url = $this->server . $friend_url;
            }
            $friend_url_path=parse_url($friend_url);
            parse_str($friend_url_path['query'],$friend_vars);
            $friend_id=$friend_vars['id'];
            
            if(!$friend_id) continue;
            
            $friend_avatar=$element->find('img',0)->src;
            $friend_name=$element->find('span.w',0)->innertext();
            
            $friend_status_img=$element->find('a img',0)->src;
            if(strstr($friend_status_img,'online'))
                $friend_status='online';
            elseif(strstr($friend_status_img,'offline'))
                $friend_status='offline';
            elseif(strstr($friend_status_img,'idle'))
                $friend_status='idle';
            elseif(strstr($friend_status_img,'busy'))
                $friend_status='busy';
            else $friend_status='unknown';
            
            if($element->find('span.s',0))
                $friend_status_text=$element->find('span.s',0)->innertext();
            else $friend_status_text='';
            $friend_url=str_replace('&amp;','&',$friend_url);
            $friend['url']=$friend_url;
            $friend['avatar']=$friend_avatar;
            $friend['id']=$friend_id;
            $friend['name']=$friend_name;
            $friend['status_img']=$friend_status_img;
            $friend['status']=$friend_status;
            $friend['status_text']=$friend_status_text;
            $friends[$friend_id]=$friend;
           // break;
        }
        $this->friends = $friends;
        return $friends;
    }
    function get_friend($id)
    {
        return $this->friends[$id];
    }
    function get_full_friend_info($id){
        global $http;
        $friend=$this->get_friend($id);
        $friend_url=$friend['url'];
        
        if(!$friend_url) return;
        $friend_info=$http->request($friend_url);
        $html=str_get_html($friend_info);
        if($html->find('div[class=p s]')){
            $status_text=$html->find('div[class=p s]',0)->innertext();
            $friend['status_text']=$status_text;
        }
        return $friend;
        //echo $friend_info;
        
    }
    function auth()
    {
        global $http;
        $post = $this->buid_post_string();
        $this->curent_page_content = $http->request($this->auth_url, $post);
    }
    function is_logged_in()
    {
        global $http;
        $content = $http->request($this->server);
        $this->curent_page_content = $content;
        if (strstr($content, 'w/login/logout')) {
            $this->logged_in = true;
        } else {
            $this->logged_in = false;
        }
        return $this->logged_in;
    }
    function get_post_var($login = true)
    {
        $post = array();
        $html = str_get_html($this->curent_page_content);
        $post = array();
        foreach ($html->find('input') as $element)
            $post[$element->name] = $element->value;
        if ($login) {
            $post['id'] = $this->nick;
            $post['password'] = $this->pass;
        }
        $this->postdata = $post;
        return $post;
    }
    function buid_post_string($postdata = '', $login = true)
    {
        if (!empty($postdata))
            $post = $postdata;
        else {
            if (empty($this->postdata)) {
                $this->get_post_var($login);
            }
            $post = $this->postdata;
        }
        $req = '';
        foreach ((array )$post as $n => $v) {
            $v = urlencode($v);
            $req .= "$n=$v&";
        }
        $req = rtrim($req, '&');
        return $req;
    }
    function get_auth_url()
    {
        global $http;
        $this->curent_page_content = $http->request($this->login_url);
        $html = str_get_html($this->curent_page_content);
        foreach ($html->find('form') as $element)
            $submit_login = $element->action;
        if (substr($submit_login, 0, 7) != 'http://') {
            $submit_login = $this->server . $submit_login;
        }
        $submit_login = urldecode($submit_login);
        $this->auth_url = $submit_login;
        return $submit_login;

    }
    function get_login_url()
    {
        $html = str_get_html($this->curent_page_content);
        foreach ($html->find('a') as $element) {
            if (strtolower($element->innertext) == 'messenger') {
                $login_url = $element->href;
                break;
            }
        }
        if (substr($login_url, 0, 7) != 'http://') {
            $login_url = $this->server . $login_url;
        }
        $login_url = urldecode($login_url);
        $this->login_url = $this->message_url = $login_url;
        return $login_url;
    }
    function get_message_url()
    {
        return $this->get_login_url();
    }
}
class Http
{
    function Http()
    {
        $this->cookie_file = dirname(__file__) . '/cookie.txt';
    }
    var $cookies = array();
    var $cookie = "";
    var $redirect = "";
    var $last_url = "";
    var $host = "";
    var $hit = 0;
    var $num_redirect = 0;
    function request($url, $req = '')
    {
        $response = $this->open($url, $req);
        return $response;
    }

    function open($url, $req = '')
    {
        $ch = curl_init($url);
        if ($req) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        }
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }

}
?>