<?php
require_once __DIR__ . '/../third_party/node.php';
if (!class_exists('\Requests')) {
    require_once __DIR__ . '/../third_party/Requests.php';
}
if (!class_exists('\Firebase\JWT\SignatureInvalidException')) {
    require_once __DIR__ . '/../third_party/php-jwt/SignatureInvalidException.php';
}
if (!class_exists('\Firebase\JWT\JWT')) {
    require_once __DIR__ . '/../third_party/php-jwt/JWT.php';
}
Requests::register_autoloader();
class CodeEnv
{
	private static $personal_token = 'msIvJdiClmzzEqmlA9hFWtIxGsRKo21e';

	function verifyPurchase($name = null, $code = null)
	{
		$CI       = &get_instance();
		if (!is_null($name) && is_null($code)) {
			$verified = false;
			if (!option_exists($name . '_is_verified') || get_option($name . '_is_verified') != 1) {
				$CI->app_modules->deactivate($name);
			}
			return $verified;
		}
		$CI->load->config($name . '/conf');
		$code = trim($code);
		$url = "https://api.envato.com/v3/market/author/sale?code=" . $code;
		$curl = curl_init($url);
		$header = array();
		$header[] = 'Authorization: Bearer ' . self::$personal_token;
		$header[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:41.0) Gecko/20100101 Firefox/41.0';
		$header[] = 'timeout: 20';
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		$envatoRes = curl_exec($curl);
		curl_close($curl);
		$envatoRes = json_decode($envatoRes);
		//var_dump($CI->config->item('product_item_id')); die;
		$data['status'] = false;
		if (isset($envatoRes) && !empty($envatoRes)) {
			// $date = new DateTime($envatoRes->supported_until);
			// $boughtdate = new DateTime($envatoRes->sold_at);
			// $bresult = $boughtdate->format('Y-m-d H:i:s');
			// $sresult = $date->format('Y-m-d H:i:s');
			// if (isset($envatoRes->item->name)) {   
			// 	$data['status'] = true;
			// } else {  
			// 	$data['status'] = false;
			// } 
			//print_r($envatoRes->item->id); die;
			if ($CI->config->item('product_item_id') != $envatoRes->item->id) {
				$data['message'] = 'Product item id not match with purchase key';
			} else {
				$CI->load->library('user_agent');
				$regData['user_agent']       = $CI->agent->browser() . ' ' . $CI->agent->version();
				$regData['activated_domain'] = base_url();
				$regData['requested_at']     = date('Y-m-d H:i:s');
				$regData['ip']               = $this->getUserIP();
				$regData['os']               = $CI->agent->platform();
				$regData['purchase_code']    = $code;
				$regData['envato_res']       = $envatoRes;
				$regData                     = json_encode($regData);
				try {
					
					$headers = ['Accept' => 'application/json'];
					print_r(REG_PROD_POINT); die;
					$request = Requests::post(REG_PROD_POINT, $headers, $regData);
					
					if ((500 <= $request->status_code) && ($request->status_code <= 599) || 404 == $request->status_code) {
						update_option($name . '_verification_id', '');
						update_option($name . '_verified', true);
						update_option($name . '_last_verification', time());
						return ['status' => true];
					}
					$response = json_decode($request->body);
					print_r($response); die;
					if (200 != $response->status) {
						return ['status' => false, 'message' => $response->message];
					}

					if (200 == $response->status) {
						$return = $response->data ?? [];
						if (!empty($return)) {
							update_option($name . '_verification_id', $return->verification_id);
							update_option($name . '_verified', true);
							update_option($name . '_last_verification', time());
							file_put_contents(__DIR__ . '/../config/token.php', $return->token);
							return ['status' => true];
						}
					}
				} catch (Exception $e) {
					update_option($name . '_verification_id', '');
					update_option($name . '_verified', true);
					update_option($name . '_last_verification', time());
					return ['status' => true];
				}
			}
		} else {
			$data['message'] = 'Something Went wrong!';
		}
		return $data;
	}
	public function validatePurchase($module_name)
    {
        $CI       = &get_instance();
        $verified = false;

        if (!option_exists($module_name.'_verification_id') || !option_exists($module_name.'_verified') || 1 != get_option($module_name.'_verified')) {
            $verified = false;
        }
        $verification_id =  get_option($module_name.'_verification_id');
        $id_data         = explode('|', $verification_id);
        if (4 != count($id_data)) {
            $verified = false;
        }

        if (file_exists(APP_MODULES_PATH.'/'.$module_name.'/config/token.php') && 4 == count($id_data)) {
            $verified = false;
            $token    = file_get_contents(APP_MODULES_PATH.'/'.$module_name.'/config/token.php');
            if (empty($token)) {
                $verified = false;
            }
            $CI->load->config($module_name.'/conf');
            try {
                $data = JWT::decode($token, $id_data[3], ['HS512']);
                if (!empty($data)) {
                    if ($CI->config->item('product_item_id') == $data->item_id && $data->item_id == $id_data[0] && $data->buyer == $id_data[2] && $data->purchase_code == $id_data[3]) {
                        $verified = true;
                    }
                }
            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                $verified = false;
            }

            $last_verification = get_option($module_name.'_last_verification');
            $seconds           = $data->check_interval ?? 0;
            if (empty($seconds)) {
                $verified = false;
            }
            if ('' == $last_verification || (time() > ($last_verification + $seconds))) {
                $verified = false;
                try {
                    $headers  = ['Accept' => 'application/json', 'Authorization' => $token];
                    $request  = Requests::post(VAL_PROD_POINT, $headers, json_encode(['verification_id'=> $verification_id, 'item_id'=> $CI->config->item('product_item_id')]));
                    if ((500 <= $request->status_code) && ($request->status_code <= 599) || 404 == $request->status_code) {
                        $verified = true;
                    } else {
                        $result   = json_decode($request->body);
                        if (!empty($result->valid)) {
                            $verified = true;
                        }
                    }
                } catch (Exception $e) {
                    $verified = true;
                }
                update_option($module_name.'_last_verification', time());
            }
        }

        if (!file_exists(APP_MODULES_PATH.'/'.$module_name.'/config/token.php') && !$verified) {
            $last_verification = (int)get_option($module_name.'_last_verification');
            if (($last_verification + (168*(3000+600))) > time()) {
                $verified = true;
            }
        }

        if (!$verified) {
            $CI->app_modules->deactivate($module_name);
        }

        return $verified;
    }
	private function getUserIP()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }
}
