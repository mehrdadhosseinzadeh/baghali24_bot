<?php

class Baghali24_botController extends Controller
{
    public $layout='//layouts/main';
    public $defaultAction = 'admin';
	
	private $api_key = '917444964:AAEe6bEAUEg8MWHpvunlQs1nD-O_95KumgE';
	private $limit = null;
	private $update = null;
	private $chat_id = null;
	private $user_id = null;
	private $user = null;
	private $message_id = null;
	private $username = null;
	private $callback_query_query = null;
	private $callback_query_action = null;
	private $callback_query_chat_id = null;
	private $callback_query_message_id = null;
	private $levels = null;
	
	private function utf8_to_unicode($utf8)
	{
		$i = 0;
		$l = strlen($utf8);

		$out = '';

		while ($i < $l) {
			if ((ord($utf8[$i]) & 0x80) === 0x00) {
				// 0xxxxxxx
				$n = ord($utf8[$i++]);
			} elseif ((ord($utf8[$i]) & 0xE0) === 0xC0) {
				// 110xxxxx 10xxxxxx
				$n =
					((ord($utf8[$i++]) & 0x1F) <<  6) |
					((ord($utf8[$i++]) & 0x3F) <<  0)
				;
			} elseif ((ord($utf8[$i]) & 0xF0) === 0xE0) {
				// 1110xxxx 10xxxxxx 10xxxxxx
				$n =
					((ord($utf8[$i++]) & 0x0F) << 12) |
					((ord($utf8[$i++]) & 0x3F) <<  6) |
					((ord($utf8[$i++]) & 0x3F) <<  0)
				;
			} elseif ((ord($utf8[$i]) & 0xF8) === 0xF0) {
				// 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
				$n =
					((ord($utf8[$i++]) & 0x07) << 18) |
					((ord($utf8[$i++]) & 0x3F) << 12) |
					((ord($utf8[$i++]) & 0x3F) <<  6) |
					((ord($utf8[$i++]) & 0x3F) <<  0)
				;
			} elseif ((ord($utf8[$i]) & 0xFC) === 0xF8) {
				// 111110xx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
				$n =
					((ord($utf8[$i++]) & 0x03) << 24) |
					((ord($utf8[$i++]) & 0x3F) << 18) |
					((ord($utf8[$i++]) & 0x3F) << 12) |
					((ord($utf8[$i++]) & 0x3F) <<  6) |
					((ord($utf8[$i++]) & 0x3F) <<  0)
				;
			} elseif ((ord($utf8[$i]) & 0xFE) === 0xFC) {
				// 1111110x 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx 10xxxxxx
				$n =
					((ord($utf8[$i++]) & 0x01) << 30) |
					((ord($utf8[$i++]) & 0x3F) << 24) |
					((ord($utf8[$i++]) & 0x3F) << 18) |
					((ord($utf8[$i++]) & 0x3F) << 12) |
					((ord($utf8[$i++]) & 0x3F) <<  6) |
					((ord($utf8[$i++]) & 0x3F) <<  0)
				;
			} else {
				throw new \Exception('Invalid utf-8 code point');
			}

			$n = strtoupper(dechex($n));
			$pad = strlen($n) <= 4 ? strlen($n) + strlen($n) %2 : 0;
			$n = str_pad($n, $pad, "0", STR_PAD_LEFT);

			$out .= sprintf("\u%s", $n);
		}

		return $out;
	}
	private function safe_html($html=null)
	{
		return htmlspecialchars(str_fix($html), ENT_QUOTES);    
	}
	private function makeHTTPRequest($method,$datas=[])
	{
		
		//return sendFromServer($method,$datas);
		$url = "https://api.telegram.org/bot".$this->api_key."/".$method;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($datas));
		$res = curl_exec($ch);
		if(curl_error($ch))
		{
			return json_decode(curl_error($ch));
		}else{
			return json_decode($res);
		}
	}
	private function sendProduct($chat_id,$message_id,$image,$caption,$keyboard)
	{
		//$this->makeHTTPRequest($api_key,'sendMessage',['chat_id'=>$chat_id,'text'=> $image]);
		try {
				$url = "https://api.telegram.org/bot".$this->api_key."/sendPhoto";
				$post = array(
					'chat_id'   => $chat_id,
					'photo'     => $image,
					'reply_to_message_id'=>$message_id,
					'caption' => $caption,
					'reply_markup'=>json_encode([
							'inline_keyboard'=>$keyboard,
							'resize_keyboard' => true, 
							'one_time_keyboard' => false,
						]
					)
				);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_POST,1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				$result=curl_exec ($ch);
				curl_close ($ch);
				$obj=json_decode($result);
				//$this->makeHTTPRequest($api_key,'sendMessage',['chat_id'=>$chat_id,'text'=> $result]);
			}catch (Exception $e) {
				//$this->makeHTTPRequest($api_key,'sendMessage',['chat_id'=>$chat_id,'text'=> 'error']);
			}

	}	
	private function isGroup()
	{
		if(isset($this->update->message->chat->type) && $this->update->message->chat->type == 'group')
		{
			$from_id = $this->update->message->from->id;
			$from_username = $this->update->message->from->username;
			$forward_from_chat_id = $this->update->message->forward_from_chat->id;
			$forward_from_chat_username = $this->update->message->forward_from_chat->username;
			$forward_from_chat_type = $this->update->message->forward_from_chat->type;
			if($forward_from_chat_type == 'channel')
			{
				$list = array('bigdata_channel');
				if(in_array($forward_from_chat_username,$list))
				{
					$this->makeHTTPRequest('deleteMessage',['chat_id'=>$this->chat_id,'message_id'=>$this->message_id]);
					
					//$this->makeHTTPRequest($api_key,'sendMessage',['user_id'=>$update->message->forward_from_cha/->id,'text'=> ' is denied!']);
				}
			}
			Yii::app()->end();
		}
		
	}	
	private function NoDuplicate()
	{

		if(isset($this->update->message->text))
		{
			
			$attribs = array('chat_id'=>$this->chat_id ,'message_id'=>$this->message_id,'update_id'=>$this->update->update_id,'type'=>'text');
			$criteria = new CDbCriteria();          
			$model = Baghali24BotProccessedmessage::model()->findByAttributes($attribs, $criteria);
			if($model){
				$model->cnt = $model->cnt + 1;
				$model->save();
				Yii::app()->end();
			}
			else
			{
				
				$model = new Baghali24BotProccessedmessage;
				$criteria=new CDbCriteria;
				$criteria->select='max(message_id) AS maxColumn';
				$attribs = array('chat_id'=>$this->chat_id,'type'=>'text');
				$row = $model->model()->find($criteria,$attribs);
				if($row)
				{
					$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->user_id,'text'=>" text=exist last request"]);
					$max = $row['maxColumn'];
					if($message_id < $max)
					{
						$model = new Baghali24BotProccessedmessage;
						$model->chat_id = $this->chat_id;
						$model->message_id = $this->message_id;
						$model->update_id = $this->update->update_id;
						$model->ts = $this->update->message->date;
						$model->text = $this->update->message->text;
						$model->type = 'text';
						$model->status = 'stopedJob';
						$model->save();
						Yii::app()->end();
					}
				}
				
				$model = new Baghali24BotProccessedmessage;
				$model->chat_id = $this->chat_id;
				$model->message_id = $this->message_id;
				$model->update_id = $this->update->update_id;
				$model->ts = $this->update->message->date;
				$model->text = $this->update->message->text;
				$model->type = 'text';
				$model->status = 'runJob';
				$model->save();
			}
		}
		
	}
	private function level()
	{
		$this->levels = Baghali24BotSessions::model()->getLevels($this->user_id);
		if($this->levels == false)
		{
			$data = array(
					'userId'=>$this->user_id,
					'actionLevel' => 1,
					'data'=>addslashes(json_encode($this->update->message->from)),
					'ts'=>time(),
					'confirmed'=>1,
			);
			Baghali24BotSessions::model()->startSession($data);
			Yii::app()->end();
		}
		
		if(count($this->levels) == 1 && isset($this->levels[1])) {$this->level_1();}
		if(count($this->levels) == 2 && isset($this->levels[2])) {$this->level_2();}
		if(count($this->levels) == 3 && isset($this->levels[3])) {$this->level_3();}
	}
	private function level_1()
	{

		if($this->levels[1]->subLevel)
		{
			if($this->levels[1]->action == '/profile')
			{
				$subLevel = $this->levels[1]->subLevel;
				switch($subLevel)
				{
					case 1:
					{
						$data = array(
								'userId'=>$this->user_id,
								'action'=>$this->update->message->text,
								'actionLevel' => 1,
								'subLevel' => $subLevel + 1,
								'data'=>addslashes(json_encode($this->update->message->from)),
								'ts'=>time(),
								'confirmed'=>1,
						);
						Baghali24BotSessions::model()->startSession($data);
						$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> 'لطفا نام و نام خانوادگی خود را وارد نمایید:','reply_markup'=>json_encode([
								'hide_keyboard' => true, 
						])]);
						break;
					}
					case 2:
					{
						$fullname = trim($this->update->message->text);
						
						$user = Baghali24BotUsers::model()->find(array(
								'condition'=>'`userid` = :userid AND `confirmed` = 1',
								'params'=>array(':userid'=>$this->user_id),
						));
						
						if($user)
						{
								$user->fullname = $fullname;
								$user->save();
						}
						else
						{

							$user = new Baghali24BotUsers;
							$user->userid = $this->user_id;
							$user->fullname = $fullname;
							$user->ts = time();
							$user->confirmed = 1;
							$user = $user->save();
							if(!$user)
							{
								$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> 'خطا در ثبت اطلاعات 1']);
							}
							else
							{
								$data = array(
										'userId'=>$this->user_id,
										'action'=>'/profile',
										'actionLevel' => 1,
										'subLevel' => $subLevel + 1,
										'data'=>addslashes(json_encode($this->update->message->from)),
										'ts'=>time(),
										'confirmed'=>1,
								);
								Baghali24BotSessions::model()->startSession($data);
								$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> 'لطفا شماره تلفن منزل خود را باذکر پیش شماره واردنمایید:']);
							}								
						}
						
						break;
					}
					case 3:
					{
							$phone = trim($this->update->message->text);
							$user = Baghali24BotUsers::model()->find(array(
									'condition'=>'`userid` = :userid AND `confirmed` = 1',
									'params'=>array(':userid'=>$this->user_id),
							));

							if($user)
							{
									$user->phone = $phone;
									$user->save();
							}
							else
							{
									$user = new Baghali24BotUsers;
									$user->userid = $this->user_id;
									$user->phone = $fullname;
									$user->ts = time();
									$user->confirmed = 1;
									if(!$user->save())
									{
											$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> var_dump($user->getErrors())]);
									}
							}

							$data = array(
									'userId'=>$this->user_id,
									'action'=>'/profile',
									'actionLevel' => 1,
									'subLevel' => $subLevel + 1,
									'data'=>addslashes(json_encode($this->update->message->from)),
									'ts'=>time(),
									'confirmed'=>1,
							);
							Baghali24BotSessions::model()->startSession($data);
							$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> 'جهت ارسال محصولات خریداره شده، لطفا کد پستی 10 رقمی خود را وارد نمایید:']);
							break;
					}	
					case 4:
					{
							$postcode = trim($this->update->message->text);

							$user = Baghali24BotUsers::model()->find(array(
									'condition'=>'`userid` = :userid AND `confirmed` = 1',
									'params'=>array(':userid'=>$this->user_id),
							));

							if($user)
							{
									$user->postcode = $postcode;
									$user->save();
							}
							else
							{
									$user = new Baghali24BotUsers;
									$user->userid = $this->user_id;
									$user->postcode = $postcode;
									$user->ts = time();
									$user->confirmed = 1;
									if(!$user->save())
									{
											$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> var_dump($user->getErrors())]);
									}

							}

							$data = array(
									'userId'=>$this->user_id,
									'action'=>'/profile',
									'actionLevel' => 1,
									'subLevel' => $subLevel + 1,
									'data'=>addslashes(json_encode($this->update->message->from)),
									'ts'=>time(),
									'confirmed'=>1,
							);
							Baghali24BotSessions::model()->startSession($data);
							$keyboard = [
									[['text'=>"Send Location",'request_location'=>true]],
									['ﺗﻤﺎﯾﻞ ﻧﺪاﺭﻡ']
							];
							$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=>'جهت ارسال دقیق محصولات خریداری شده لطفا ادرس محل سکونت خود را از طریق نقشه برای ما ارسال نمایید:', 'reply_markup'=>json_encode([
									'keyboard' => $keyboard, 
									'resize_keyboard' => true, 
									'one_time_keyboard' => true,
							])]);
							break;
					}
					case 5:
					{

							if(isset($this->update->message->location))
							{
									$user = Baghali24BotUsers::model()->find(array(
											'condition'=>'`userid` = :userid AND `confirmed` = 1',
											'params'=>array(':userid'=>$this->user_id),
									));

									require 'Googl.class.php';
									$googl = new Googl('AIzaSyCjllMnVmrm1TzXxPqKK1XVcMTYGtcixpk');

									$latitude = $this->update->message->location->latitude;
									$longitude = $this->update->message->location->longitude;

									$mapsurl = "http://maps.google.com/maps?q={$latitude},{$longitude}";
									$mapsurl = $googl->shorten($mapsurl);

									if($user)
									{
											$user->location = $mapsurl;
											$user->save();
									}
									else
									{
											$user = new Baghali24BotUsers;
											$user->userid = $this->user_id;
											$user->location = $mapsurl;
											$user->ts = time();
											$user->confirmed = 1;
											if(!$user->save())
											{
													$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> var_dump($user->getErrors())]);
											}

									}

									$data = array(
											'userId'=>$this->user_id,
											'action'=>'/profile',
											'actionLevel' => 1,
											'subLevel' => $subLevel + 1,
											'data'=>addslashes(json_encode($this->update->message->from)),
											'ts'=>time(),
											'confirmed'=>1,
									);
									Baghali24BotSessions::model()->startSession($data);
									$keyboard = [
											[['text'=>"اﺭﺳﺎﻝ ﺷﻤﺎﺭﻩ",'request_contact'=>true]],
											 ['ﺗﻤﺎﯾﻞ ﻧﺪاﺭﻡ']
											];
									$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=>'لطفا تلفن همراه خود را وارد نمایید:', 'reply_markup'=>json_encode([
													'keyboard' => $keyboard, 
													'resize_keyboard' => true, 
													'one_time_keyboard' => true,
											])]);
							}
							else
							{
									if($this->update->message->text == 'ﺗﻤﺎﯾﻞ ﻧﺪاﺭﻡ')
									{
											// bye default is null or set null to remove last location
											$user = Baghali24BotUsers::model()->find(array(
													'condition'=>'`userid` = :userid AND `confirmed` = 1',
													'params'=>array(':userid'=>$this->user_id),
											));
											$data = array(
													'userId'=>$this->user_id,
													'action'=>'/profile',
													'actionLevel' => 1,
													'subLevel' => $subLevel + 1,
													'data'=>addslashes(json_encode($this->update->message->from)),
													'ts'=>time(),
													'confirmed'=>1,
											);
											Baghali24BotSessions::model()->startSession($data);
											$keyboard = [
											[['text'=>"اﺭﺳﺎﻝ ﺷﻤﺎﺭﻩ",'request_contact'=>true]],
											 ['تماسل ندارم']
											];
											$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=>'لطفا تلفن همراه خود را وارد نمایید:', 'reply_markup'=>json_encode([
													'keyboard' => $keyboard, 
													'resize_keyboard' => true, 
													'one_time_keyboard' => true,
											])]);								
									}
									else
									{
											$msg = 'ﺩﺭ ﺻﻮﺭﺕ ﺗﻤﺎﯾﻞ اﺯ ﻃﺮﯾﻖ ﺩﮐﻤﻪ ﺯﯾﺮ ﻣﻮﻗﻌﯿﺖ ﻣﮑﺎﻧﯽ ﻣﺤﻞ ﺯﻧﺪﮔﯽ ﺧﻮﺩ ﺭا ﺑﺮاﯼ ﻣﺎ اﺭﺳﺎﻝ ﻧﻤﺎﯾﯿﺪ.';
											$keyboard = [
													[['text'=>"Send Location",'request_location'=>true]],
													['ﺗﻤﺎﯾﻞ ﻧﺪاﺭﻡ']
											];
											$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=>$msg, 'reply_markup'=>json_encode([
													'keyboard' => $keyboard, 
													'resize_keyboard' => true, 
													'one_time_keyboard' => true,
											])]);
									}

							}
							break;
					}
					case 6:
					{

								if(isset($this->update->message->contact))
								{
										$user = Baghali24BotUsers::model()->find(array(
												'condition'=>'`userid` = :userid AND `confirmed` = 1',
												'params'=>array(':userid'=>$this->user_id),
										));

										if($user)
										{
												$user->mobile = $this->update->message->contact->phone_number;
												$user->save();
										}
										else
										{
												$user = new Baghali24BotUsers;
												$user->userid = $this->user_id;
												$user->mobile = $this->update->message->contact->phone_number;
												$user->ts = time();
												$user->confirmed = 1;
												if(!$user->save())
												{
														$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> var_dump($user->getErrors())]);
												}

										}

										$data = array(
												'userId'=>$this->user_id,
												'action'=> NULL,
												'actionLevel' => 1,
												'subLevel' => NULL,
												'data'=>addslashes(json_encode($this->update->message->from)),
												'ts'=>time(),
												'confirmed'=>1,
										);
										Baghali24BotSessions::model()->startSession($data);
										$keyboard = [
												[['text'=>"home"]],
												];
										$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=>'پروفایل شما با موفقییت ویرایش شد', 'reply_markup'=>json_encode([
														'keyboard' => $keyboard, 
														'resize_keyboard' => true, 
														'one_time_keyboard' => true,
												])]);
								}
								else
								{
										if($this->update->message->text == 'ﺗﻤﺎﯾﻞ ﻧﺪاﺭﻡ')
										{
												// bye default is null or set null to remove last location
												$user = Baghali24BotUsers::model()->find(array(
														'condition'=>'`userid` = :userid AND `confirmed` = 1',
														'params'=>array(':userid'=>$this->user_id),
												));
												$data = array(
														'userId'=>$this->user_id,
														'action'=> NULL,
														'actionLevel' => 1,
														'subLevel' => NULL,
														'data'=>addslashes(json_encode($this->update->message->from)),
														'ts'=>time(),
														'confirmed'=>1,
												);
												Baghali24BotSessions::model()->startSession($data);
												$keyboard = [
														[['text'=>"home"]],
												];
												$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=>'پروفایل شما با موفقییت ویرایش شد', 'reply_markup'=>json_encode([
																'keyboard' => $keyboard, 
																'resize_keyboard' => true, 
																'one_time_keyboard' => true,
														])]);							
										}
										else
										{
												//??
										}

								}
								break;
						}

				}
				Yii::app()->end();
			}
		}
		//start validMessage code block
		$attribs = array('level'=>1 ,'confirmed'=>1);
		$criteria = new CDbCriteria(array('order'=>'sort'));
		$validMessageModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
		
		$validMessage = array();
		$validMessageInfo = array();
		foreach($validMessageModel as $item)
		{
			$validMessage[] = $item->menu;
			$validMessageInfo[] = array('id'=>$item->id,'type'=>$item->type);
		} 
		//end validMessage code block

		//start getKeyboard code block
		$keyboard = array();

		$attribs = array('level'=>1 ,'confirmed'=>1);
		$criteria = new CDbCriteria(array('order'=>'sort'));
		$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
		
		
		$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
						array('level'=>1),
						'confirmed=1'
				);
		
		$levelChunkMenuModel = $levelChunkMenuModel[0];
		$chunks = explode(';', $levelChunkMenuModel->chunk);

		$i = 0;
		$row = 0;
		foreach($chunks as $chunk)
		{
			for($j=1;$j<=$chunk;$j++)
			{
					$keyboard[$row][] = $levelMenuModel[$i]->menu;
					$i++;
			}
			$row++;
		}
		//end getKeyboard code block

		if(!in_array($this->update->message->text, $validMessage))
		{
				//check 13 code
				$text = 'موردی یافت نشد.';
				$text .= "\n";
				$text .= 'یکی از گزینه های زیر رو انتخاب کنید یا کد 13 رقمی کالا رو برام بفرستید‌:';
				$text .= "--";

				//start getKeyboard code block
				$keyboard = array();

				$attribs = array('level'=>1 ,'confirmed'=>1);
				$criteria = new CDbCriteria(array('order'=>'sort'));
				$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
				
				$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
								array('level'=>1),
								'confirmed=1'
						);
				$levelChunkMenuModel = $levelChunkMenuModel[0];
				$chunks = explode(';', $levelChunkMenuModel->chunk);

				$i = 0;
				$row = 0;
				foreach($chunks as $chunk)
				{
						for($j=1;$j<=$chunk;$j++)
						{
								$keyboard[$row][] = $levelMenuModel[$i]->menu;
								$i++;
						}
						$row++;
				}
				//end getKeyboard code block

				$this->makeHTTPRequest('sendMessage',[
								'chat_id'=>$this->chat_id,
								'reply_to_message_id'=>$this->message_id,
								'text'=> $text,
								'reply_markup'=>json_encode([
										'keyboard' => $keyboard, 
										'resize_keyboard' => true, 
										'one_time_keyboard' => false,
								])]);

				Yii::app()->end();         
		}

		//Set next level keyboard
		$nextLevelKeyboard = array();

		$mainMenuModel = Baghali24BotMainmenu::model()->findAllByAttributes(
				array('parent'=>$this->update->message->text),
				'confirmed=1'
		);

		if(is_array($mainMenuModel) && count($mainMenuModel)) 
		{

			$key = array_search($this->update->message->text, $validMessage);
			$i = 0;
			$keyboard = array();
			$keyboard[$i] = array("back");
			$rows = array_chunk($mainMenuModel, 4);
			$i++;
			foreach($rows as $item)
			{
					foreach($item as $each)
							$keyboard[$i][] = $each->content;
					$i++;
			}
			
			$nextLevelKeyboard[$key] = $keyboard;
			
			$nextLevelText = array();
			$nextLevelText[$key] = 'دسته بندی مورد نظر خود را انتخاب نمایید :';
		}

		$key = array_search($this->update->message->text, $validMessage);

		if(isset($nextLevelKeyboard[$key]))
		{

				$data = array(
						'userId'=>$this->user_id,
						'action'=>$this->update->message->text,
						'actionLevel'=>2,
						'data'=>addslashes(json_encode($this->update->message->from)),
						'ts'=>time(),
						'confirmed'=>1,
				);
				Baghali24BotSessions::model()->addSession($data);
				// go next level

				$text = $nextLevelText[$key];
				$r = $this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'reply_to_message_id'=>$this->message_id, 'text'=> $text,
						'reply_markup'=>json_encode([
								'keyboard' => $nextLevelKeyboard[$key], 
								'resize_keyboard' => true, 
								'one_time_keyboard' => false,
						])]);
		}
		else
		{

			$info = $validMessageInfo[$key];
			if($info['type'] == 'simple')
			{
				$attribs = array('levelmenuid'=>$info['id']);
				$content = Baghali24BotLevelmenucontent::model()->findByAttributes($attribs);
				$content = $content->text;
			   
				// NEW CODE
				$keyboard = array();
				
				$attribs = array('level'=>1 ,'confirmed'=>1);
				$criteria = new CDbCriteria(array('order'=>'sort'));
				$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
				
				$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
							array('level'=>1),
							'confirmed=1'
						);
				$levelChunkMenuModel = $levelChunkMenuModel[0];
				$chunks = explode(';', $levelChunkMenuModel->chunk);

				$i = 0;
				$row = 0;
				foreach($chunks as $chunk)
				{
					for($j=1;$j<=$chunk;$j++)
					{
							$keyboard[$row][] = $levelMenuModel[$i]->menu;
							$i++;
					}
					$row++;
				}
				//end getKeyboard code block

				$this->makeHTTPRequest('sendMessage',[
								'chat_id'=>$this->chat_id,
								'reply_to_message_id'=>$this->message_id,
								'text'=> $content,
								'reply_markup'=>json_encode([
										'keyboard' => $keyboard, 
										'resize_keyboard' => true, 
										'one_time_keyboard' => false,
								])]);

				Yii::app()->end();
			}
			else
			{
				//tuch home button

				$data = array(
						'userId'=>$this->user_id,
						'actionLevel' => 1,
						'data'=>addslashes(json_encode($this->update->message->from)),
						'ts'=>time(),
						'confirmed'=>1,
				);

				Baghali24BotSessions::model()->startSession($data);
				$text = 'یکی از گزینه های زیر رو انتخاب کنید یا کد 13 رقمی کالا رو برام بفرستید‌:';
				$text .= "--";
				//start getKeyboard code block
				$keyboard = array();

				$attribs = array('level'=>1 ,'confirmed'=>1);
				$criteria = new CDbCriteria(array('order'=>'sort'));
				$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
		
				$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
								array('level'=>1),
								'confirmed=1'
						);
				$levelChunkMenuModel = $levelChunkMenuModel[0];
				$chunks = explode(';', $levelChunkMenuModel->chunk);

				$i = 0;
				$row = 0;
				foreach($chunks as $chunk)
				{
					for($j=1;$j<=$chunk;$j++)
					{
							$keyboard[$row][] = $levelMenuModel[$i]->menu;
							$i++;
					}
					$row++;
				}
				//end getKeyboard code block
				$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> $text,'reply_markup'=>json_encode([
										'keyboard' => $keyboard, 
										'resize_keyboard' => true, 
										'one_time_keyboard' => false,
								])]);
				Yii::app()->end();
		}

		}
		Yii::app()->end();
		
	}
	private function level_2()
	{
		$criteria = new CDbCriteria( array(
				'condition' => "`parent` LIKE '{$this->levels[2]->action}' AND `confirmed` = 1",
		) );

		$categories = Baghali24BotMainmenu::model()->findAll( $criteria );

		$category = array();		
		foreach($categories as $item)
		{
			$category[] = $item->content;
		}

		$validMessage = array(
			'back',
		);

		$validMessage = array_merge($validMessage,$category);
		if(!in_array($this->update->message->text, $validMessage))
		{
			//check 13 code
			$text = 'موردی یافت نشد.';
			$text .= "\n";
			$text .= 'یکی از گزینه های زیر رو انتخاب کنید یا کد 13 رقمی کالا رو برام بفرستید‌:';
			$text .= "--";
			$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'reply_to_message_id'=>$this->message_id,'text'=> $text]);

			Yii::app()->end();          
		}

		$nextLevelKeyboard = array();
		$criteria = new CDbCriteria();
		$criteria->distinct=true;
		$criteria->condition = 'level2Action=:level2Action AND level3Action=:level3Action';      
		$criteria->select = 'category';
		$criteria->params = array(':level2Action' => $this->levels[2]->action, ':level3Action'=>$this->update->message->text);

		$subMenu = Baghali24BotProduct::model()->findAll($criteria);

		//moshkel to get all or get fix item
		if(is_array($subMenu) && count($subMenu)) 
		{
			$key = array_search($this->update->message->text, $validMessage);

			//Set next level keyboard
			$i = 0;
			$keyboard = array();
			$keyboard[$i] = array("back","home");

			$rows = array_chunk($subMenu, 4);
			$i++;
			foreach($rows as $item)
			{
					foreach($item as $each)
							$keyboard[$i][] = $each->category;
					$i++;
			}

			$nextLevelKeyboard[$key] = $keyboard;

			$nextLevelText = array();
			$nextLevelText[$key] = 'دسته بندی مورد نظر خود را انتخاب نمایید :';
		}

			$key = array_search($this->update->message->text, $validMessage);


			if(isset($nextLevelKeyboard[$key]))
			{

				$data = array(
						'userId'=>$this->user_id,
						'action'=>$this->update->message->text,
						'actionLevel'=>3,
						'data'=>addslashes(json_encode($this->update->message->from)),
						'ts'=>time(),
						'confirmed'=>1,
				);
				Baghali24BotSessions::model()->addSession($data);
				// go next level
				$text = $nextLevelText[$key];
				$r = $this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'reply_to_message_id'=>$this->message_id, 'text'=> $text,
						'reply_markup'=>json_encode([
								'keyboard' => $nextLevelKeyboard[$key], 
								'resize_keyboard' => true, 
								'one_time_keyboard' => false,
						])]);
			}
			else if($key == 0)
			{
				//if $key == 1 is home and else is back
				$data = array(
						'userId'=>$this->user_id,
						'actionLevel' => 1,
						'data'=>addslashes(json_encode($this->update->message->from)),
						'ts'=>time(),
						'confirmed'=>1,
				);

				Baghali24BotSessions::model()->startSession($data);
				$text = 'یکی از گزینه های زیر رو انتخاب کنید یا کد 13 رقمی کالا رو برام بفرستید‌:';
				$text .= "--";
				//start getKeyboard code block
				$keyboard = array();

				$attribs = array('level'=>1 ,'confirmed'=>1);
				$criteria = new CDbCriteria(array('order'=>'sort'));
				$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
				
				$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
								array('level'=>1),
								'confirmed=1'
						);
				$levelChunkMenuModel = $levelChunkMenuModel[0];
				$chunks = explode(';', $levelChunkMenuModel->chunk);

				$i = 0;
				$row = 0;
				foreach($chunks as $chunk)
				{
					for($j=1;$j<=$chunk;$j++)
					{
							$keyboard[$row][] = $levelMenuModel[$i]->menu;
							$i++;
					}
					$row++;
				}
				//end getKeyboard code block
				$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> $text,'reply_markup'=>json_encode([
										'keyboard' => $keyboard, 
										'resize_keyboard' => true, 
										'one_time_keyboard' => false,
								])]);
				Yii::app()->end();

			}
			else{
							//check 13 code
				$text = 'برای دسته بندی مورد نظر محصولی درج نشده است';
				$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'reply_to_message_id'=>$this->message_id,'text'=> $text]);

				Yii::app()->end();
			}

			Yii::app()->end();
	}
	private function level_3()
	{
		$validMessage = array(
				'back',
				'home',
				);

		$criteria = new CDbCriteria( array(
				'select'=>'category',
				'condition' => "`level2Action` LIKE '{$this->levels[2]->action}' AND `level3Action` = '{$this->levels[3]->action}'",
		) );

		$menus = Baghali24BotProduct::model()->findAll( $criteria );

		$lastMenuItems = array();
		foreach($menus as $item)
		{
			$lastMenuItems[] = $item->category;
		}
		
		$lastMenuItems = array_unique($lastMenuItems);

		$validMessage = array_merge($validMessage,$lastMenuItems);

		if(!in_array($this->update->message->text, $validMessage)){
				//check 13 code
				$text = 'موردی یافت نشد.';
				$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'reply_to_message_id'=>$this->message_id,'text'=> $text]);
				/*	
				makeHTTPRequest('sendMessage',['chat_id'=>$chat_id,'reply_to_message_id'=>$message_id,'text'=> $text,'reply_markup'=>json_encode([
								'keyboard' => $keyboard, 
								'resize_keyboard' => true, 
								'one_time_keyboard' => false,
						])]);
				*/		
				Yii::app()->end();          
		}


		$key = array_search($this->update->message->text, $validMessage);

		if(in_array($this->update->message->text, $lastMenuItems))
		{
				$criteria = new CDbCriteria( array(
						'condition' => "`level2Action` LIKE '{$this->levels[2]->action}' AND `level3Action` LIKE '{$this->levels[3]->action}' AND `category` LIKE '{$this->update->message->text}'",
						'order'=>'id DESC',
				) );
				$products = Baghali24BotProduct::model()->findAll($criteria);
				$products_count = count($products);

				$caption = $products[0]->id;
				$caption .= "\n";
				$caption .= 'قیمت: '.$products[0]->price.' ت';
				$caption .= "\n";
				$caption .= "#".$this->levels[3]->action;

				$products_count = $products_count-1;

				$next = (($products_count>=$this->limit)?$this->limit:$products_count);
				if($next)
				{
					$pkeyboard = array(
										array(
											array('text'=>"جزئیات",'callback_data'=>'productinfo:'.$products[0]->id),
										),
										array(
											array('text'=>'همه مدل ها','switch_inline_query_current_chat'=>$this->levels[2]->action.'>'.$this->levels[3]->action.'>'.$this->update->message->text),
											array('text'=>$next.' مدل بعدی','callback_data'=>'showproduct:page=1&category='.$this->update->message->text),
										),
										array(
											array('text'=>"سفارش محصول",'callback_data'=>'orderproduct:'.$products[0]->id),
										)
									);
					

				}
				else{
						
						$pkeyboard = array(
										array(
											array('text'=>"جزئیات",'callback_data'=>'productinfo:'.$products[0]->id),
										),
										array(
											array('text'=>"سفارش محصول",'callback_data'=>'orderproduct:'.$products[0]->id),
										)
									);				
				}

				//$path = dirname(__FILE__).DIRECTORY_SEPARATOR.'shahab2.png';
					//$response = json_decode($products[0]->image);
					//$count = count($response->result->photo);
					//$file_id = $response->result->photo[$count-1]->file_id;
				$images = json_decode($products[0]->image);
				$file_id = $images[count($images)-1]->file_id;
				//$this->makeHTTPRequest($api_key,'sendMessage',['chat_id'=>$chat_id,'text'=> 'beforesending'.file_get_contents('php://input')]);
				$result = $this->sendProduct($this->chat_id,$this->message_id,$file_id,$caption,$pkeyboard);
				///makeHTTPRequest('sendMessage',['chat_id'=>$chat_id,'text'=> $result]);
				//TblShahabbabolBotProccessedmessage.php
				Yii::app()->end();
		}
		else
		{
			$key = array_search($this->update->message->text, $validMessage);
			if($key == 1) 
			{
					$data = array(
							'userId'=>$this->user_id,
							'actionLevel' => 1,
							'data'=>addslashes(json_encode($this->update->message->from)),
							'ts'=>time(),
							'confirmed'=>1,
					);

					Baghali24BotSessions::model()->startSession($data);
					$text = 'یکی از گزینه های زیر رو انتخاب کنید یا کد 13 رقمی کالا رو برام بفرستید‌:';
					$text .= "--";
					//start getKeyboard code block
					$keyboard = array();

					$attribs = array('level'=>1 ,'confirmed'=>1);
					$criteria = new CDbCriteria(array('order'=>'sort'));
					$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);
		
					$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
									array('level'=>1),
									'confirmed=1'
							);
					$levelChunkMenuModel = $levelChunkMenuModel[0];
					$chunks = explode(';', $levelChunkMenuModel->chunk);

					$i = 0;
					$row = 0;
					foreach($chunks as $chunk)
					{
						for($j=1;$j<=$chunk;$j++)
						{
								$keyboard[$row][] = $levelMenuModel[$i]->menu;
								$i++;
						}
						$row++;
					}
					//end getKeyboard code block
					$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> $text,'reply_markup'=>json_encode([
											'keyboard' => $keyboard, 
											'resize_keyboard' => true, 
											'one_time_keyboard' => false,
									])]);
					Yii::app()->end();
			}
			else
			{
				// back step

				$backLevelKeyboard = array();

				$mainMenuModel = Baghali24BotMainmenu::model()->findAllByAttributes(
						array('parent'=>$this->levels[2]->action),
						'confirmed=1'
				);

				if(is_array($mainMenuModel) && count($mainMenuModel)) 
				{
					$i = 0;
					$keyboard = array();
					$keyboard[$i] = array("back");

					$rows = array_chunk($mainMenuModel, 4);
					$i++;
					foreach($rows as $item)
					{
						foreach($item as $each){
							$keyboard[$i][] = $each->content;
						}
						$i++;
					}

					$backLevelKeyboard[0] = $keyboard;

					$backLevelText = array();
					$backLevelText[0] = 'دسته بندی مورد نظر خود را انتخاب نمایید :';
					if(count($backLevelKeyboard))
					{
						if(Baghali24BotSessions::model()->removeSession($this->levels[3]->id))
						{
							$text = $backLevelText[0];
							$r = $this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'reply_to_message_id'=>$this->message_id, 'text'=> $text,
									'reply_markup'=>json_encode([
											'keyboard' => $backLevelKeyboard[0], 
											'resize_keyboard' => true, 
											'one_time_keyboard' => false,
									])]);
							Yii::app()->end();

						}
						else
						{
								//to home
						}
					}
					else
					{
							//to home
					}
				}
				else
				{
						//to home
				}



			}
		}

		Yii::app()->end();
	}
	private function start()
	{
		
		$data = array(
				'userId'=>$this->user_id,
				'actionLevel' => 1,
				'data'=>addslashes(json_encode($this->update->message->from)),
				'ts'=>time(),
				'confirmed'=>1,
		);

		Baghali24BotSessions::model()->startSession($data);
		$text = 'یکی از گزینه های زیر رو انتخاب کنید یا کد 13 رقمی کالا رو برام بفرستید‌:';
		$text .= "\n";
		$text .= "--";
		//start getKeyboard code block
		$keyboard = array();


		$attribs = array('level'=>1 ,'confirmed'=>1);
		$criteria = new CDbCriteria(array('order'=>'sort'));
		$levelMenuModel = Baghali24BotLevelmenu::model()->findAllByAttributes($attribs, $criteria);

		$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
						array('level'=>1),
						'confirmed=1'
				);
		//makeHTTPRequest('sendMessage',['chat_id'=>$chat_id,'text'=> count($levelChunkMenuModel)]);
		$levelChunkMenuModel = $levelChunkMenuModel[0];
		$chunks = explode(';', $levelChunkMenuModel->chunk);

		$i = 0;
		$row = 0;
		foreach($chunks as $chunk)
		{
			for($j=1;$j<=$chunk;$j++)
			{
					$keyboard[$row][] = $levelMenuModel[$i]->menu;
					$i++;
			}
			$row++;
		}
		//end getKeyboard code block
		$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->chat_id,'text'=> $text,'reply_markup'=>json_encode([
								'keyboard' => $keyboard, 
								'resize_keyboard' => true, 
								'one_time_keyboard' => false,
						])]);

		Yii::app()->end();
	}
	private function profile()
	{
		if($this->update->message->text == '/profile')
		{
			$data = array(
					'userId'=>$this->user_id,
					'action'=>$this->update->message->text,
					'actionLevel' => 1,
					'subLevel' => 1,
					'data'=>addslashes(json_encode($this->update->message->from)),
					'ts'=>time(),
					'confirmed'=>1,
			);
			Baghali24BotSessions::model()->startSession($data);
		}
	}
	private function inline_query()
	{
		$query = $this->update->inline_query->query;
		$query = explode('<',$query);
		
		$mResult = [
			[
				"type" => "article",
				"id" => "first",
				"title" => "ارسال آدرس",
				"message_text" => "/getAddress".$query[1],
				"parse_mode" => "Markdown",
				"description" => "شما از طریق لینک بالا قادر خواهید بود آدرس مکان فعلی خود را برای ما ارسال کنید",
			]
			
			
		];
		
		$this->makeHTTPRequest('answerInlineQuery',[
				'inline_query_id'=>$this->update->inline_query->id,
				'cache_time' => 0,
				'results' => json_encode($mResult),

		]);

		Yii::app()->end();
	}
	private function callback_query()
	{
		$this->callback_query_chat_id = $this->update->callback_query->message->chat->id;
		$this->callback_query_message_id = $this->update->callback_query->message->message_id;			
		$data = $this->update->callback_query->data;
		/*
		$attribs = array('chat_id'=>$chat_id ,'message_id'=>$message_id,'update_id'=>$update->update_id,'type'=>'inline');
		$criteria = new CDbCriteria();          
		$model = TblShahabbabolBotProccessedmessage::model()->findByAttributes($attribs, $criteria);
		if($model){
			die();
		}
		
		$model = new TblShahabbabolBotProccessedmessage;
		$criteria=new CDbCriteria;
		$criteria->select='max(message_id) AS maxColumn';
		$attribs = array('chat_id'=>$chat_id,'type'=>'inline');
		$row = $model->model()->find($criteria,$attribs);
		$max = $row['maxColumn'];
		if($max) {
		if($message_id < $max)
			die();
		}
		
		$model = new TblShahabbabolBotProccessedmessage;
		$model->chat_id = $chat_id;
		$model->message_id = $message_id;
		$model->update_id = $update->update_id;
		$model->ts = time();
		$model->text = $data;
		$model->type = 'inline';
		$model->save();
		*/		
		///////////
		$query = explode(':',$data);
		$this->callback_query_query = $query;
		if(!count($query)==2){Yii::app()->end();}
		$action = $query[0];
		$this->callback_query_action = $action;
		if($this->callback_query_action == 'delorder'){$this->callback_query_delorder();}
		else if($this->callback_query_action == 'productinfo'){$this->callback_query_productinfo();}
		else if($this->callback_query_action == 'increaseorder'){$this->callback_query_increaseorder();}
		else if($this->callback_query_action == 'decreaseorder'){$this->callback_query_decreaseorder();}
		else if($this->callback_query_action == 'buyproduct'){$this->callback_query_buyproduct();}
		else if($this->callback_query_action == 'orderproduct'){$this->callback_query_orderproduct();}
		else if($this->callback_query_action == 'showproduct'){$this->callback_query_showproduct();}
		else if($this->callback_query_action == 'paymenttype'){$this->callback_query_paymenttype();}
		
		
		Yii::app()->end();
	}
	private function callback_query_delorder()
	{
		$query = $this->callback_query_query[1];
		parse_str($query, $params);
		$this->makeHTTPRequest('deleteMessage',[
						'chat_id'=>$params['chat_id'],
						'message_id'=>$params['message_id'],
				]);
		$this->makeHTTPRequest('answerCallbackQuery',[
						'callback_query_id'=>$this->update->callback_query->id,
						'text'=>'سفارش لغو شد',
						'show_alert'=>true
				]);
	}
	private function callback_query_productinfo()
	{
		$product_id = $this->callback_query_query[1];
		$product = Baghali24BotProduct::model()->findByPk($product_id);
		if($product)
		{
			$callbackMessage = $product->text;
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>$callbackMessage,
					'show_alert'=>true
			]);
		}
	}
	private function callback_query_paymenttype()
	{
		$parts = explode('&',$this->callback_query_query[1]);
		$product_id = explode('=',$parts[0])[1];
		$count = explode('=',$parts[1])[1];
		
		$message_id = explode('=',$parts[2])[1];

		$product = Baghali24BotProduct::model()->findByPk($product_id);
		if(!$product)
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'چنین محصولی برای خرید وجود ندارد و یا از سیستم فروش حذف گردیده است',
					'show_alert'=>true
			]);
			Yii::app()->end();
		}	
		else if(($product->price * $count)>100)
		{
			$description = "نام محصول : ";
			$description .= $product->text;
			$description .= "\r\n";
			$description .= "تعداد : ";
			$description .= $count;
			$description .= "\r\n";
			$description .= "قیمت : ";
			$description .= number_format($product->price).' ریال';
			$description .= "\r\n";
			$description .= "\r\n";
			$description .= "نوع پرداخت؟";
			
			$response = $this->makeHTTPRequest('editMessageText',[
					'chat_id'=>$this->callback_query_chat_id,
					'message_id'=>$message_id,
					'text'=> $description,
					'selective'=>true,
					'reply_markup'=>json_encode([
							'inline_keyboard'=>[
									[
											['text'=>"پرداخت آنلاین",'callback_data'=>'buyproduct:p_id='.$product->id.'&count='.$count.'&paymenttype=online'],
											['text'=>"پرداخت در محل",'callback_data'=>'buyproduct:p_id='.$product->id.'&count='.$count.'&paymenttype=inlocation']
									]
							]
					])
			]);
		
		}
		else
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'مبلغ تراکنش از حد مجاز کمتر می باشد',
					'show_alert'=>true
			]);				
		}
	}
	private function callback_query_orderproduct()
	{
		$product_id = $this->callback_query_query[1];
		$product = Baghali24BotProduct::model()->findByPk($product_id);
		if(!$product)
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'چنین محصولی برای خرید وجود ندارد و یا از سیستم فروش حذف گردیده است',
					'show_alert'=>true
			]);
			Yii::app()->end();	
		}	
		else if($product->price>100)
		{
			$count = 1;
			$description = "نام محصول : ";
			$description .= $product->text;
			$description .= "\r\n";
			$description .= "تعداد : ";
			$description .= $count;
			$description .= "\r\n";
			$description .= "قیمت : ";
			$description .= number_format($product->price).' ریال';
			
			$response = $this->makeHTTPRequest('sendMessage',[
					'chat_id'=>$this->callback_query_chat_id,
					'reply_to_message_id'=>$this->callback_query_message_id,
					'text'=> $description,
					'selective'=>true,
					'reply_markup'=>json_encode([
							'inline_keyboard'=>[
									[
											['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x96").'"'),'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id],
											['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id]

											//['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$chat_id.'&message_id='.$message_id.'&product_id=1'],
											//['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$chat_id.'&message_id='.$message_id.'&product_id=1'],
									],
									[
											['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9D\x8C").'"')."حذف سفارش ",'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id]
									],
									[
											//['text'=>"تکمیل خرید",'callback_data'=>'buyproduct:p_id='.$product->id.'&count='.$count],
											['text'=>"تکمیل خرید",'callback_data'=>'paymenttype:p_id='.$product->id.'&count='.$count]
									]
							]
					])
			]);
		
			
			if($response->ok)
			{
				$this->makeHTTPRequest('editMessageText',[
						'chat_id'=>$this->callback_query_chat_id,
						'message_id'=>$response->result->message_id,
						'text'=> $description,
						'reply_markup'=>json_encode([
								'inline_keyboard'=>[
										[
												['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x96").'"'),'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$response->result->message_id],
												['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$response->result->message_id.'&p_id='.$product->id]
										],
										[
												['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9D\x8C").'"')."حذف سفارش ",'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$response->result->message_id]
										],
										[
												//['text'=>"تکمیل خرید",'callback_data'=>'buyproduct:p_id='.$product->id.'&count='.$count],
												['text'=>"تکمیل خرید",'callback_data'=>'paymenttype:p_id='.$product->id.'&count='.$count.'&message_id='.$response->result->message_id]
										]
								]
						])

				]);
			}
			Yii::app()>end();				
		}
		else
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'مبلغ تراکنش از حد مجاز کمتر می باشد',
					'show_alert'=>true
			]);				
		}
	}
	private function callback_query_increaseorder()
	{
		$parts = explode('&',$this->callback_query_query[1]);
		$count = explode('=',$parts[0])[1];
		$chat_id = explode('=',$parts[1])[1];
		$message_id = explode('=',$parts[2])[1];
		$product_id = explode('=',$parts[3])[1];

		$user_id = $this->update->callback_query->from->id;
		$product = Baghali24BotProduct::model()->findByPk($product_id);
		if(!$product)
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'چنین محصولی برای خرید وجود ندارد و یا از سیستم فروش حذف گردیده است',
					'show_alert'=>true
			]);
			Yii::app()->end();
		}	
		else if($product->price>100)
		{
			$count += 1;
			$description = "نام محصول : ";
			$description .= $product->text;
			$description .= "\r\n";
			$description .= "تعداد : ";
			$description .= $count;
			$description .= "\r\n";
			$description .= "قیمت : ";
			$description .= number_format($product->price * $count).' ریال';

			$this->makeHTTPRequest('editMessageText',[
				'chat_id'=>$this->callback_query_chat_id,
				'message_id'=>$this->callback_query_message_id,
				'text'=> $description,
				'reply_markup'=>json_encode([
						'inline_keyboard'=>[
								[
										['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x96").'"'),'callback_data'=>'decreaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id],
										['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id]
								],
								[
										['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9D\x8C").'"')."حذف سفارش ",'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id]
								],
								[
										//['text'=>"تکمیل خرید",'callback_data'=>'buyproduct:p_id='.$product->id.'&count='.$count]
										['text'=>"تکمیل خرید",'callback_data'=>'paymenttype:p_id='.$product->id.'&count='.$count.'&message_id='.$this->callback_query_message_id]
								]
						]
				])

			]);
		}
		else{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'مبلغ تراکنش از حد مجاز کمتر می باشد',
					'show_alert'=>true
			]);				
		}

		Yii::app()->end();
	}
	private function callback_query_decreaseorder()
	{
		$parts = explode('&',$this->callback_query_query[1]);
		$count = explode('=',$parts[0])[1];
		$chat_id = explode('=',$parts[1])[1];
		$message_id = explode('=',$parts[2])[1];
		$product_id = explode('=',$parts[3])[1];

		$user_id = $this->update->callback_query->from->id;
		$product = Baghali24BotProduct::model()->findByPk($product_id);
		if(!$product)
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'چنین محصولی برای خرید وجود ندارد و یا از سیستم فروش حذف گردیده است',
					'show_alert'=>true
			]);
			Yii::app()->end();
		}	
		else if($product->price>100)
		{
			$count -= 1;
			if($count<1)
			{
				$count = 1;
			}
			/*
			if($count == 0)
			{
					makeHTTPRequest('deleteMessage',[
							'chat_id'=>$chat_id,
							'message_id'=>$message_id,
					]);
					makeHTTPRequest('answerCallbackQuery',[
							'callback_query_id'=>$update->callback_query->id,
							'text'=>'سفارش لغو شد',
							'show_alert'=>true
					]);
					exit();
			}
			*/
			$description = "نام محصول : ";
			$description .= $product->text;
			$description .= "\r\n";
			$description .= "تعداد : ";
			$description .= $count;
			$description .= "\r\n";
			$description .= "قیمت : ";
			$description .= number_format($product->price * $count).' ریال';

			$this->makeHTTPRequest('editMessageText',[
									'chat_id'=>$this->callback_query_chat_id,
									'message_id'=>$this->callback_query_message_id,
									'text'=> $description,
									'reply_markup'=>json_encode([
											'inline_keyboard'=>[
													[
															['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x96").'"'),'callback_data'=>'decreaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id],
															['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id]
													],
													[
															['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9D\x8C").'"')."حذف سفارش ",'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id]
													],
													[
															//['text'=>"تکمیل خرید",'callback_data'=>'buyproduct:p_id='.$product->id.'&count='.$count]
															['text'=>"تکمیل خرید",'callback_data'=>'paymenttype:p_id='.$product->id.'&count='.$count.'&message_id='.$this->callback_query_message_id]
													]
											]
									])

							]);
		}
		else
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'مبلغ تراکنش از حد مجاز کمتر می باشد',
					'show_alert'=>true
			]);				
		}

		Yii::app()->end();
	}
	private function callback_query_buyproduct()
	{
		$parts = explode('&',$this->callback_query_query[1]);
		$product_id = explode('=',$parts[0])[1];
		$count = explode('=',$parts[1])[1];
		$paymenttype = explode('=',$parts[2])[1];
		$this->makeHTTPRequest('answerCallbackQuery',[
							'callback_query_id'=>$this->update->callback_query->id,
							'text'=>'pay type : '.$paymenttype,
							'show_alert'=>true
					]);
		$product = Baghali24BotProduct::model()->findByPk($product_id);
		if(!$product)
		{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'چنین محصولی برای خرید وجود ندارد و یا از سیستم فروش حذف گردیده است',
					'show_alert'=>true
			]);
			Yii::app()->end();
		}	
		else if(($product->price * $count)>100)
		{
			$user = Baghali24BotUsers::model()->findByAttributes(
					array('userid'=>$this->update->callback_query->from->id),
					'confirmed=:confirmed',
					array(':confirmed'=>1)
			);
			
			if(!$user)
			{
					$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->callback_query_chat_id,'text'=>'لطفا پروفایل خود را تکمیل نمایید 👇']);
					$this->makeHTTPRequest('sendMessage',['chat_id'=>$this->callback_query_chat_id,'text'=>'/profile']);
					Yii::app()->end();				
			}
				
			$update_data = array(
				'chat_id'=> $this->callback_query_chat_id,
				'callback_query_id'=> $this->update->callback_query->id,
			);
			
			$amount = ($product->price * $count);
			
			$ts = time();
			$pin = '23532cfe28b8';
			$order = new Baghali24BotOrders;
			
			$order->userid = $this->update->callback_query->from->id;
			$order->product_id = $product->id;
			$order->botname = 'baghali24';
			$order->update_data = json_encode($update_data);
			$order->ip = '0.0.0.0';
			$order->amount = $amount;
			$order->ts = $ts;
			$order->paid = 0;
			$order->confirmed = 1;
			
			
			if($order->save())
			{
				$callback = "tg://resolve?domain=baghali24_bot";
				$callback = 'https://konkor.me/bot/index.php/site/botpay';
				$client = new SoapClient("http://payment.farafekr.co/index.php/payment/wsdl", array("encoding"=>"UTF-8"));
				$au = $client->request($pin , round($amount) , $callback , $order->id , urlencode(date('Y/m/d H:i:s', $ts)));
				if(strlen($au)>8)
				{
					$url = 'https://konkor.me/bot/index.php/site/botpay/code/'.$au;
					
					$description = "نام محصول : ";
					$description .= $product->text;
					$description .= "\r\n";
					$description .= "تعداد : ";
					$description .= $count;
					$description .= "\r\n";
					$description .= "قیمت : ";
					$description .= number_format($product->price * $count).' ریال';

					$this->makeHTTPRequest('editMessageText',[
											'chat_id'=>$this->callback_query_chat_id,
											'message_id'=>$this->callback_query_message_id,
											'text'=> $description,
											'reply_markup'=>json_encode([
													'inline_keyboard'=>[
															[
																	['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x96").'"'),'callback_data'=>'decreaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id],
																	['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9E\x95").'"'),'callback_data'=>'increaseorder:count='.$count.'&chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id.'&p_id='.$product->id]
															],
															[
																	['text'=>json_decode('"'.$this->utf8_to_unicode("\xE2\x9D\x8C").'"')."حذف سفارش ",'callback_data'=>'delorder:chat_id='.$this->callback_query_chat_id.'&message_id='.$this->callback_query_message_id]
															],
															[
																	['text'=>'💰'." پرداخت",'url'=> $url],

															]
													]
											])

					]);
					//makeHTTPRequest('sendMessage',['chat_id'=>$chat_id,'text'=>$url]);
				}
				else{
					$this->makeHTTPRequest('answerCallbackQuery',[
							'callback_query_id'=>$this->update->callback_query->id,
							'text'=>'خخطا در اتصال به سرور بانک',
							'show_alert'=>true
					]);
				}
			}
			else
			{
				$this->makeHTTPRequest('answerCallbackQuery',[
						'callback_query_id'=>$this->update->callback_query->id,
						'text'=>'خطا در ثبت عملیات،لطفا مجدادا تلاش نمایید',
						'show_alert'=>true
				]);
			}  
			Yii::app()->end();	
		}
		else{
			$this->makeHTTPRequest('answerCallbackQuery',[
					'callback_query_id'=>$this->update->callback_query->id,
					'text'=>'مبلغ تراکنش از حد مجاز کمتر می باشد',
					'show_alert'=>true
			]);				
		}

	}

	private function callback_query_showproduct()
	{
		$parts = explode('&',$this->callback_query_query[1]);
		$page = explode('=',$parts[0])[1];
		$category = explode('=',$parts[1])[1];
		$user_id = $this->update->callback_query->from->id;

		$levels = Baghali24BotSessions::model()->getLevels($user_id);
		if($levels == false)
		{
			Yii::app()>end();	
		}

		$criteria = new CDbCriteria( array(
				'condition' => "`level2Action` LIKE '{$levels[2]->action}' AND `level3Action` LIKE '{$levels[3]->action}' AND `category` LIKE '{$category}'"
		));	

		$products = Baghali24BotProduct::model()->findAll($criteria);
		$count_all = count($products);
		
		$criteria = new CDbCriteria( array(
				'condition' => "`level2Action` LIKE '{$levels[2]->action}' AND `level3Action` LIKE '{$levels[3]->action}' AND `category` LIKE '{$category}'",
				'order'=>'id DESC',
				'limit'=>$this->limit,
				'offset'=>$page,
		));
		
		$products = Baghali24BotProduct::model()->findAll($criteria);
		
		if(count($products)==1)
		{
			$caption = $products[0]->id;
			$caption .= "\n";
			$caption .= 'قیمت: 32،000 ت';
			$caption .= "\n";
			$caption .= "#".$levels[3]->action;

			$pkeyboard = array(
					array(
									array('text'=>"جزئیات",'callback_data'=>'productinfo:'.$products[0]->id),
							),
							array(
									array('text'=>"سفارش محصول",'callback_data'=>'orderproduct:'.$products[0]->id),
							)
			);
			$images = json_decode($products[0]->image);
			$file_id = $images[count($images)-1]->file_id;
			$this->sendProduct($this->callback_query_chat_id,$this->callback_query_message_id,$file_id,$caption,$pkeyboard);
			Yii::app()>end();	
		}

		$page += $this->limit;
		
		for($i=0;$i<count($products)-1;$i++)
		{
			
			$caption = $products[$i]->id;
			$caption .= "\n";
			$caption .= 'قیمت: 32،000 ت';
			$caption .= "\n";
			$caption .= "#".$levels[3]->action;

			$pkeyboard = array(
					array(
						array('text'=>"جزئیات",'callback_data'=>'productinfo:'.$products[$i]->id),
					),
					array(
							array('text'=>"سفارش محصول",'callback_data'=>'orderproduct:'.$products[$i]->id),
					)
			);
				
			$images = json_decode($products[$i]->image);
			$file_id = $images[count($images)-1]->file_id;
			
			$result = $this->sendProduct($this->callback_query_chat_id,$this->callback_query_message_id,$file_id,$caption,$pkeyboard);
			
			$this->makeHTTPRequest('sendChatAction',['chat_id'=>$this->callback_query_chat_id,'action'=> 'در حال ارسال لیست محصولات']);
			sleep(0.5);
				
		}

		
		$next = ($page+$this->limit)<$count_all?$this->limit:$count_all-$page;
		
		$caption = $products[(count($products)-1)]->id;
		$caption .= "\n";
		$caption .= 'قیمت: 32،000 ت';
		$caption .= "\n";
		$caption .= "#".$levels[3]->action;
		if($next>0)
		{
			$pkeyboard = array(
					array(
						array('text'=>"جزئیات",'callback_data'=>'productinfo:'.$products[(count($products)-1)]->id),
					),
					array(
						array('text'=>'همه مدل ها','switch_inline_query_current_chat'=>$levels[2]->action.'>'.$levels[3]->action.'>'.$category),
									array('text'=>$next.' مدل بعدی','callback_data'=>'showproduct:page='.$page.'&category='.$category),
							),
							array(
									array('text'=>"سفارش محصول",'callback_data'=>'orderproduct:'.$products[(count($products)-1)]->id),
							)
			);
		}
		else
		{
			$pkeyboard = array(
					array(
									array('text'=>"جزئیات",'callback_data'=>'productinfo:'.$products[(count($products)-1)]->id),
							),
					array(

									array('text'=>'همه مدل ها','switch_inline_query_current_chat'=>$levels[2]->action.'>'.$levels[3]->action.'>'.$category),
							),
							array(
									array('text'=>"سفارش محصول",'callback_data'=>'orderproduct:'.$products[(count($products)-1)]->id),
							)
			);				
		}
		
		$images = json_decode($products[(count($products)-1)]->image);
		$file_id = $images[count($images)-1]->file_id;
		$this->sendProduct($this->callback_query_chat_id,$this->callback_query_message_id,$file_id,$caption,$pkeyboard);
		
		Yii::app()->end();
	}
	public function actionIndex()
	{	
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		/* comment code  //header('HTTP/1.1 200 OK'); //http_response_code(200); header("Connection: close"); die('end line'); $this->makeHTTPRequest($api_key,	*/
		if(!json_decode(file_get_contents("php://input")))
		{
			Yii::app()->end('No update!');
		}
		$this->update = json_decode(file_get_contents("php://input"));
		//$this->update = json_decode('{"update_id":991526374,"message":{"message_id":17189,"from":{"id":135163496,"is_bot":false,"first_name":"Mehrdad","last_name":"Hosseinzadeh","username":"mehrdadhosseinzadeh","language_code":"fa"},"chat":{"id":135163496,"first_name":"Mehrdad","last_name":"Hosseinzadeh","username":"mehrdadhosseinzadeh","type":"private"},"date":1566322833,"text":"bacardi"}}');
		
		if(isset($this->update->message->chat->id))
			$this->chat_id = $this->update->message->chat->id;
		if(isset($this->update->message->from->id))
			$this->user_id = $this->update->message->from->id;
		if(isset($this->update->message->message_id))
			$this->message_id = $this->update->message->message_id;
		if(isset($this->update->message->chat->username))
			$this->username = $this->update->message->chat->username; 
			
			
		$this->isGroup();
		$this->limit = 3;
		//$this->NoDuplicate($api_key,$update,$chat_id,$message_id,$user_id);
		if(isset($this->update->inline_query))
		{
			$this->inline_query();
		}
		if(isset($this->update->callback_query))
		{
			$this->callback_query();
		}
		
		$arr = array('/start','شروع');
		if(isset($this->update->message->text) && in_array($this->update->message->text,$arr))
		{
			$this->start();
		}

		$arr = array('/profile');
		if(isset($this->update->message->text) && in_array($this->update->message->text,$arr))
		{
			$this->profile();
		}
		$this->level();
		
	}
    public function actionAdmin()
    {
        $this->render('admin');
    }
    public function actionLevelmenu($opt=null,$id=null)
    {
        if(!$opt)
        {
            //this is index or admin request page
             $dataProvider = new CActiveDataProvider('Baghali24BotLevelmenu',
                     array('sort'=>array('defaultOrder'=>'id DESC')));
		$this->render('levelmenu',array(
			'dataProvider'=>$dataProvider,
		));
        }
        else if($opt == 'create')
        {
            $model = new Baghali24BotLevelmenu;
            if(isset($_POST['Levelmenu']))
            {
                $model->attributes=$_POST['Levelmenu'];

                $criteria = new CDbCriteria;
                $criteria->order = 'sort DESC';
                $row = Baghali24BotLevelmenu::model()->find($criteria);
                $max = $row->sort;
                $model->sort = ++$max;
                
                $criteria = new CDbCriteria;
                $criteria->addCondition("level=:level AND confirmed=1");
                $criteria->params = array(':level' => 1);
                $chunkModel = Baghali24BotLevelchunkmenu::model()->find($criteria);
                $chunkModel->chunk = $chunkModel->chunk.';1';
                $chunkModel->save();
                
                if($model->type == 'simple')
                {
                    $content = $_POST['Levelmenu']['content'];
                    if($content == '')
                    {
                        $model->addError('type','محتوای پیام را وارد کنید');
                    }
                    else
                    {
               
                        
                        if($model->save())
                        {
                            $contentModel = new Baghali24BotLevelmenucontent;
                            $contentModel->levelmenuid = $model->id;
                            $contentModel->text = $content;
                            if($contentModel->save())
                            {
                                $this->redirect(array('levelmenu'));
                            }
                            else
                            {
                                throw new CHttpException(500,'can not save on create1');
                            }
                        }
                    }
                }
                else
                {
                    if($model->save())
                    {
                        $this->redirect(array('levelmenu'));
                    }
                    else
                    {
                        throw new CHttpException(500,'can not save on create2');
                    }
                }
            }
            $this->render('levelmenu_form',array(
                    'model'=>$model,));
        }
        else if($opt == 'update')
        {
            if(!$id)
            {
                throw new CHttpException(404,'id ra vared kon.');
            }  
            $model=$this->levelmenuloadModel($id);
            $lastmenu = $model->menu;
            if(isset($_POST['Levelmenu']))
            {
                $type = $model->type;
                $model->attributes=$_POST['Levelmenu'];
                if(isset($model->type))
                    unset($model->type);
                
                if($type == 'simple')
                {
                    $content = $_POST['Levelmenu']['content'];;
                    if($content == '')
                    {
                        $model->addError('type','محتوای پیام را وارد کنید');
                    }
                    else
                    {
                        if($model->save())
                        {
                            $contentModel = Baghali24BotLevelmenucontent::model()->findByPk($model->contents[0]->id);
                            $contentModel->text = $content;
                            if($contentModel->save())
                            {
                                $this->redirect(array('levelmenu'));
                            }else
                            {
                                throw new CHttpException(500,'can not save on update');
                            }
                        }    
                    }
                }
                else
                {
                    unset($model->content);
                    
                    if($model->save())
                    {
                        if($type == 'next')
                        {
                            //$modelProduct = TblShahabbabolBotProduct::model()->findAllByAttributes(array('level2Action'=>$lastmenu));
                            //$modelProduct->level2Action = $model->menu;
                            //$modelProduct->save();
                            Baghali24BotProduct::model()->updateAll(
                                    array('level2Action' => $model->menu),
                                    'level2Action=:level2Action',
                                    array(':level2Action'=>$lastmenu)
                                    );
                            Baghali24BotMainmenu::model()->updateAll(
                                    array('parent' => $model->menu),
                                    'parent=:parent',
                                    array(':parent'=>$lastmenu)
                                    );
                        }
                        $this->redirect(array('levelmenu'));
                    }
                }
            }
            $this->render('levelmenu_form',array(
                    'model'=>$model,));
        }
        else if($opt == 'delete')
        {
            if(!$id)
            {
                throw new CHttpException(404,'id ra vared konn.');
            }
            
            $levelmenuModel = Baghali24BotLevelmenu::model()->findByPk($id);
			

            $menu = $levelmenuModel->menu;
            // Start the transaction
            $transaction = Yii::app()->db->beginTransaction();
            try {
                Baghali24BotProduct::model()->deleteAllByAttributes(
                    array('level2Action'=>$menu));
                $transaction->commit();
                $transaction2 = Yii::app()->db->beginTransaction();
                try {
                    Baghali24BotMainmenu::model()->deleteAllByAttributes(
                        array('parent'=>$menu));
                    $transaction2->commit();
					$sort = $levelmenuModel->sort;
					$levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findByAttributes(
									array('level'=>1),
									'confirmed=1'
							);
					$chunks = explode(';', $levelChunkMenuModel->chunk);
					$i = 0;
					$index = -1;
					foreach($chunks as $key=>$chunk)
					{
					   $i += $chunk;
					   if($sort<=$i)
					   {
						   $index = $key;
						   break;
					   }
					}
					$chunks[$index]--;
					$chunk = implode(';',$chunks);
					
					$levelChunkMenuModel->chunk = $chunk;
					$levelChunkMenuModel->save();
                    $levelmenuModel->delete();
                }
                catch (Exception $e2) {
                    $transaction2->rollback();
                }
            }
            catch (Exception $e) {
                $transaction->rollback();
            }

            if(!isset($_GET['ajax']))
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('levelmenu'));
        }
       
    }
    public function actionCategory($levelid=null,$opt=null,$id=null)
    {
        if(!$opt)
        {
            if(!$levelid)
            {
                throw new CHttpException(400,'id level menu ra vared konid');
            }
            
            $levelmenu = $this->levelmenuloadModel($levelid);
            if(!$levelmenu)
            {
                throw new CHttpException(404,'does not exist levelmenu');
            }
            //this is index or admin request page
            $dataProvider = new CActiveDataProvider('Baghali24BotMainmenu',
                    array('criteria'=>array('condition'=>'parent=:parent','params'=>array(':parent'=>$levelmenu->menu))),
                    array('sort'=>array('defaultOrder'=>'id DESC')));
            $this->render('category',array(
                    'dataProvider'=>$dataProvider,
                    'levelmenu' => $levelmenu
            ));
        }
        else if($opt == 'create')
        {
            if(!$levelid)
            {
                throw new CHttpException(400,'id level menu ra vared konid');
            }
            
            $levelmenu = $this->levelmenuloadModel($levelid);
            if(!$levelmenu)
            {
                throw new CHttpException(404,'does not exist levelmenu');
            }
            
            $model = new Baghali24BotMainmenu;
            if(isset($_POST['category']))
            {
                $model->attributes=$_POST['category'];
                $model->parent = $levelmenu->menu;
                if($model->save())
                {
                    $this->redirect(array('category','levelid'=>$levelid));
                }
            }
            $this->render('category_form',array(
                    'model'=>$model,
                    'levelmenu'=>$levelmenu,
                ));
        }
        else if($opt == 'update')
        {
            if(!$levelid)
            {
                throw new CHttpException(400,'id level menu ra vared konid');
            }
            
            $levelmenu = $this->levelmenuloadModel($levelid);
            if(!$levelmenu)
            {
                throw new CHttpException(404,'does not exist levelmenu');
            }
            if(!$id)
            {
                 throw new CHttpException(400,'id ra vared konid');
            }  
            $model = $this->categoryloadModel($id);
            $lastcat = $model->content;
            if($model===null)
                throw new CHttpException(404,'id ra vared kon.');
            
            if(isset($_POST['category']))
            {
                $model->attributes=$_POST['category'];
                if($model->save())
                {
                     Baghali24BotProduct::model()->updateAll(
                                    array('level3Action' => $model->content),
                                    'level2Action=:level2Action AND level3Action=:level3Action',
                                    array(':level2Action'=>$model->parent,':level3Action'=>$lastcat)
                                    );
                    $this->redirect(array('category','levelid'=>$levelid));
                }
            }
            $this->render('category_form',array(
                    'model'=>$model,
                    'levelmenu'=>$levelmenu,
                ));
        }
        else if($opt == 'delete')
        {
            if(!$id)
            {
                throw new CHttpException(404,'id ra vared kon.');
            }
            $catmodel = Baghali24BotMainmenu::model()->findByPk($id);
            $parent = $catmodel->parent;
            $content = $catmodel->content;
            $transaction = Yii::app()->db->beginTransaction();
            try
            {
                Baghali24BotProduct::model()->deleteAllByAttributes(
                    array('level2Action'=>$parent,'level3Action'=>$content));
                $transaction->commit();
                $transaction2 = Yii::app()->db->beginTransaction();
                try
                {
                    $this->categoryloadModel($id)->delete();
                   
                    $transaction2->commit();
                }
                catch(Exception $e2)
                {
                    $transaction2->rollback();
                    $transaction->rollback();
                }
            }
            catch(Exception $e)
            {
                $transaction->rollback();
            }
            
            
            if(!isset($_GET['ajax']))
                    $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('category','levelid'=>$levelid));
        }
       
    }
    public function sendImage($token,$chat_id,$file)
    {
        try {
                $url = "https://api.telegram.org/bot".$token."/sendPhoto";
                $post = array(
                        'chat_id'=> $chat_id,
                        'photo'  => '@'.$file,
                );
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_POST,1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                $result=curl_exec ($ch);
                curl_close ($ch);
                $obj = json_decode($result);
            }
            catch (Exception $e) {}
            return $obj;
    }
    public function actionProductimage($id)
    {
        if(!$id)
        {
            throw new CHttpException(400,'id ra vared konid');
        }
        $model = $this->productsLoadMmodel($id);
        $bot = Bots::model()->findByAttributes(
                            array('username'=>'baghali24_bot')
                        );
        $token = $bot->token;
        $file = Telegram::getImage($token, $model->image);
        $img = getimagesize($file);
        $content = file_get_contents($file);
        $this->renderPartial('productimage',array(
            'content'=>$content,
            'filename'=>$file,
            'type'=>$img['mime'],
        ));
       


    }
    public function actionProducts($levelid=null,$catid=null,$opt=null,$id=null)
    {
        if(!$opt)
        {
            if(!$levelid || !$catid)
            {
                throw new CHttpException(400, 'Levelid va Menuid ra vared konid');
            }
            $levelmenu = $this->levelmenuloadModel($levelid);
            $category = $this->categoryloadModel($catid);
            
            $criteria = new CDbCriteria;
            $criteria->condition = "level2Action=:level2 AND level3Action=:level3";
            $criteria->params=(array(':level2'=>$levelmenu->menu,':level3'=>$category->content));
            $dataProvider = new CActiveDataProvider('Baghali24BotProduct', 
                    array('criteria'=>$criteria),
                    array('sort'=>array('defaultOrder'=>'id DESC')));
            
            $bot = Bots::model()->findByAttributes(
                            array('username'=>'baghali24_bot')
                        );
            $token = $bot->token;
            $this->render('products',array(
                    'dataProvider'=>$dataProvider,
                    'levelmenu' => $levelmenu,
                    'category' => $category,
                    'token'=>$token,
            ));
        }
        // -------------------------------------------
        else if($opt == 'create')
        {
            if(!$levelid || !$catid)
            {
                throw new CHttpException(400, 'Levelid va Menuid ra vared konid');
            }
            
            $levelmenu = $this->levelmenuloadModel($levelid);
            $category = $this->categoryloadModel($catid);
            $model = new Baghali24BotProduct;

            if(isset($_POST['Products']))
            {
                if($file = CUploadedFile::getInstanceByName('Products[productfile]')) 
                {
                    $validTypes = array('image/jpeg','image/jpg','image/png');
                    if(!$file || $file->error != 0 || !in_array($file->type, $validTypes)) 
                    {
                        $model->addError("image", 'فایل تصویر محصول دارای اشکال است');
                    }
                    else 
                    {
                        $path = dirname(__FILE__).DIRECTORY_SEPARATOR.time().'.png';
                        $file->saveAs($path);
                        $bot = Bots::model()->findByAttributes(
                            array('username'=>'baghali24_bot')
                        );

                        $response = $this->sendImage($bot->token,'152461225',$path);
                        if(!$response->ok)
                            $model->addError("image", 'خطا در آپلود تصویر به سرور تلگرام');
                        $model->image = json_encode($response);
                    }

                }
                $model->attributes = $_POST['Products'];
                $model->level2Action = $levelmenu->menu;
                $model->level3Action = $category->content;
                if(!$model->hasErrors() && $model->save())
                {
                    $this->redirect(array('products','levelid'=>$levelmenu->id,'catid'=>$category->id));
                }
            }
            
            $this->render('products_form',array(
                    'model'=>$model,
                    'levelmenu'=>$levelmenu,
                    'category' => $category
                ));
        }
        else if($opt == 'update')
        {
            if(!$levelid || !$catid)
            {
                throw new CHttpException(400, 'Levelid va Menuid ra vared konid');
            }
            if(!$id)
            {
                 throw new CHttpException(400,'id ra vared konid');
            }
            $levelmenu = $this->levelmenuloadModel($levelid);
            $category = $this->categoryloadModel($catid);
            $model = $this->productsLoadMmodel($id);
            if(isset($_POST['Products']))
            {
                if($file = CUploadedFile::getInstanceByName('Products[productfile]')) 
                {
                    $validTypes = array('image/jpeg','image/jpg','image/png');
                    if(!$file || $file->error != 0 || !in_array($file->type, $validTypes)) 
                    {
                        $model->addError("image", 'فایل تصویر محصول دارای اشکال است');
                    }
                    else 
                    {
                        $path = dirname(__FILE__).DIRECTORY_SEPARATOR.time().'.png';
                        $file->saveAs($path);
                        $bot = Bots::model()->findByAttributes(
                            array('username'=>'baghali24_bot')
                        );

                        $response = $this->sendImage($bot->token,'152461225',$path);
                        if(!$response->ok)
                            $model->addError("image", 'خطا در آپلود تصویر به سرور تلگرام');
                        $model->image = json_encode($response);
                    }

                }
                $model->attributes=$_POST['Products'];
                if($model->save())
                {
                    $this->redirect(array('products','levelid'=>$levelmenu->id,'catid'=>$category->id));
                }
            }
            $this->render('products_form',array(
                    'model'=>$model,
                    'levelmenu'=>$levelmenu,
                    'category' => $category
                ));
        }
        else if($opt == 'delete')
        {
            $levelmenu = $this->levelmenuloadModel($levelid);
            $category = $this->categoryloadModel($catid);
            if(!$id)
            {
                throw new CHttpException(404,'id ra vared kon.');
            }
            $this->productsLoadMmodel($id)->delete();
            if(!isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('products','levelid'=>$levelmenu->id,'catid'=>$category->id));
        }
    }
    public function levelmenuloadModel($id)
    {
            $model = Baghali24BotLevelmenu::model()->findByPk($id);
            if($model===null)
                throw new CHttpException(404,'The requested page does not exist(Levelmenu).');
            return $model;
    }
    public function categoryloadModel($id)
    {
            $model = Baghali24BotMainmenu::model()->findByPk($id);
            if($model===null)
                throw new CHttpException(404,'The requested page does not exist(category).');
            return $model;
    }
    public function productsLoadMmodel($id)
    {
        $model = Baghali24BotProduct::model()->findByPk($id);
        if($model===null)
            throw new CHttpException(404,'The requested page does not exist(products).');
        return $model;
    }
    public function actionSortmenu()
    {
        if(Yii::app()->request->isAjaxRequest) 
        {
            if(!(isset($_POST['items']) && isset($_POST['chunks'])))
                exit();
            $items = $_POST['items'];
            $items = explode(',',$items);
            $chunks = $_POST['chunks'];
            $chunks = explode(',',$chunks);
            $count = array_sum($chunks);
            if(count($items) != $count)
                exit();
            ////  safe condition
            Baghali24BotLevelchunkmenu::model()->updateAll(
                array('chunk' => implode(';', $chunks)),
                'level=:level',
                array(':level'=>1)
            );

            $criteria = new CDbCriteria;
            $criteria->addInCondition( "id" , $items ) ;
            $levelmenus = Baghali24BotLevelmenu::model()->findAll($criteria);
            foreach($levelmenus as $levelmenu)
            {
                $sort = -1;
                for($i=0;$i<count($items);$i++)
                {
                    if($items[$i] == $levelmenu->id )
                    {
                        $sort = $i;
                        break;
                    }
                }
                $eachModel = Baghali24BotLevelmenu::model()->findByPk($levelmenu->id);
                $eachModel->sort = $sort;
                $eachModel->save();
            }
            /*
            // *****************************************************************
            $crit = new CDbCriteria();
            $crit->select = '*';
            $crit->condition = 'level=1';
            $crit->order = 'sort';
            $levelmenu = Baghali24BotLevelmenu::model()->findAll($crit);
            
            $keyboard = array();
            $levelChunkMenuModel = TblShahabbabolBotLevelchunkmenu::model()->findAllByAttributes(
                            array('level'=>1),
                            'confirmed=1'
                    );
            $levelChunkMenuModel = $levelChunkMenuModel[0];
            $chunks = explode(';', $levelChunkMenuModel->chunk);
            $i = 0;
            $row = 0;
            foreach($chunks as $chunk)
            {
                for($j=1;$j<=$chunk;$j++)
                {
                        $keyboard[$row][] = array('dbid'=>$levelmenu[$i]->id,'text'=>$levelmenu[$i]->menu);
                        $i++;
                }
                $row++;
            }
            $this->render('sortmenu',array('levelmenu'=>$levelmenu,'keyboard'=>$keyboard));
            */
            exit();
        }
        
        // End of isAjaxRequest
        
        $crit = new CDbCriteria();
        $crit->select = '*';
        $crit->condition = 'level=1';
        $crit->order = 'sort';
        $levelmenu = Baghali24BotLevelmenu::model()->findAll($crit);
        // *************************************************************
        $keyboard = array();
        $levelChunkMenuModel = Baghali24BotLevelchunkmenu::model()->findAllByAttributes(
                        array('level'=>1),
                        'confirmed=1'
                );
        $levelChunkMenuModel = $levelChunkMenuModel[0];
        $chunks = explode(';', $levelChunkMenuModel->chunk);
        $i = 0;
        $row = 0;
        foreach($chunks as $chunk)
        {
            for($j=1;$j<=$chunk;$j++)
            {
                    $keyboard[$row][] = array('dbid'=>$levelmenu[$i]->id,'text'=>$levelmenu[$i]->menu);
                    $i++;
            }
            $row++;
        }
        // ********************************************************************
        $this->render('sortmenu',array('levelmenu'=>$levelmenu,'keyboard'=>$keyboard));
    }
    
    
}
