<?php

	include "comm.php";

	if (isset($_POST["reason"])) {
		
		include "conn.php";

		// print_r($_FILES["LeaveFile"]);

//		$startTime = $_POST["startTime"];
//		$endTime = $_POST["endTime"];
		$time =explode(' - ', $_POST['time_interval']);
		$startTime = $time[0];
		$endTime = $time[1];
		$reason = $_POST["reason"];
		$remark = $_POST["remark"];

		$username = $_SESSION["username"];
		$name = $_SESSION["sn"].$_SESSION["givenname"];

		$idHeader = date("Y")."0".ceil(date("m")/3);
		$sql = "select max(id) from leavenote";
		$sql_rs = $mysqli -> query($sql);
		if($rs = mysqli_fetch_array($sql_rs))
		{
			$idFooter = sprintf("%04d", ($rs[0] + 1));//生成4位数，不足前面补0 
			$number = $idHeader.$idFooter;
		}
		else
		{
			echo "Get Info Error";
			exit();
		}

		$days=abs((strtotime($endTime)-strtotime($startTime))/86400);


		//(暂)指定人事部的唐玲玲进行审核
		$rs_sql = $mysqli -> query("SELECT uid FROM user WHERE department = '人事部' AND name = '唐玲玲'");
		if($rs = mysqli_fetch_array($rs_sql))
		{
			$accepted = $rs[0];
		}


		$rs_sql = $mysqli -> query("SELECT uid FROM user WHERE username = '{$username}'");
		if($rs = mysqli_fetch_array($rs_sql)){
			$uid = $rs[0];
		}

//		$rs_sql = $mysqli -> query("SELECT top, uid FROM user WHERE username = '{$username}'");
//		if (mysqli_num_rows($rs_sql) > 0)
//		{
//			$rs = mysqli_fetch_array($rs_sql);
//			$uid = $rs["uid"];
//			$accepted = $uid;
//			if (($top = $rs["top"])!= null)
//			{
//				if($days <= 2)
//				{
//					$accepted = $top;
//				}
//				else
//				{
//					$rs_sql = $mysqli -> query("SELECT top, uid FROM user WHERE username = '{$top}'");
//					if(mysqli_num_rows($rs_sql) > 0)
//					{
//						$rs = mysqli_fetch_array($rs_sql);
//						$accepted = $top;
//						if (($topTop = $rs["top"])!= null)
//						{
//							if($days <= 5)
//							{
//								$accepted = $topTop;
//							}
//							else
//							{
//								$rs_sql = $mysqli -> query("SELECT top, uid FROM user WHERE username = '{$topTop}'");
//								if (mysqli_num_rows($rs_sql) > 0)
//								{
//									$rs = mysqli_fetch_array($rs_sql);
//									if (($topTopTop = $rs["top"])!= null)
//									{
//										$accepted = $topTopTop;
//									}
//								}
//								else
//								{
//									echo "User's Top's Top Info Error";
//								}
//							}
//						}
//					}
//					else
//					{
//						echo "User's Top Info Error";
//					}
//
//				}
//			}
//		}
//		else
//		{
//			echo "User Info Error";
//		}


		if (!empty($_FILES["LeaveFile"]["tmp_name"]))
		{
			$accessory_error = $_FILES["LeaveFile"]["error"];
			$accessory_type = $_FILES["LeaveFile"]["type"];
			// echo "type:".$accessory_type."<br/>";
			$accessory_size = $_FILES["LeaveFile"]["size"];
			$accessory_tmp_name = $_FILES["LeaveFile"]["tmp_name"];
			$accessory_name = $_FILES["LeaveFile"]["name"];
			if($accessory_error > 0)
			{
				echo -1; //文件上传失败
				exit();
			}
			// $accessory_fp = fopen($accessory_tmp_name, 'r');
			// $accessory_content = fread($accessory_fp, $accessory_size);
			// fclose($accessory_fp);
			// echo $accessory;

			// $accessory_content = addslashes($accessory_content);

			//检测上传文件
			if($accessory_type != 'image/png' && $accessory_type != 'image/jpeg')
			{
				echo "File Type Error";
				exit();
			}
			if ($accessory_size > 8 * 1024 * 1024) {
				echo "File Size Error";
				exit();
			}
			$file_name = $number.".".pathinfo($accessory_name)["extension"];
			move_uploaded_file($accessory_tmp_name, "..\\data\\leavenote\\".$file_name);
		}
		else
		{
			$file_name = null;
		}
		

		// echo date('Y-m-d');


		$count = manhourCount($startTime, $endTime);

		$sql = "INSERT INTO leavenote (number, uid, name, startTime, endTime, reason, attachment, remark, accepted, submitDate, state, count) VALUES ('{$number}', '{$uid}', '{$name}', '{$startTime}', '{$endTime}', '{$reason}', '{$file_name}', '{$remark}','{$accepted}','" . date('Y-m-d') . "','未处理', {$count})";
		// echo $sql;
		$rs = $mysqli -> query($sql);


		if(mysqli_affected_rows($mysqli) > 0)
			echo "0";
		else

			echo "1";
	}

	function manhourCount($startT, $endT)
	{
		$start = split("[- :]",$startT);
		$end = split("[- :]",$endT);
		$m1 = intval($start[3]) * 60 + intval($start[4]);
		$m2 = intval($end[3]) * 60 + intval($end[4]);
		//确定与工作时间相交的时间区间
		if ($m1 <= (8 * 60 + 30) || $m1 >= 18 * 60){
			$m1 = 0;
		}else if ($m1 >= (12 * 60) && $m1 <= (13 * 60 + 30)){
			$m1 = 3.5;
		}else if($m1 <= (12 * 60)){
			$m1 = ($m1 - (8 * 60 + 30)) / 60;
		}else{
			$m1 = ($m1 - (8 * 60 + 30) - (1.5 * 60)) / 60;
		}

		if ($m2 <= (8 * 60 + 30) || $m2 >= 18 * 60){
			$m2 = 0;
		}else if ($m2 >= (12 * 60) && $m2 <= (13 * 60 + 30)){
			$m2 = 3.5;
		}else if($m2 <= (12 * 60)){
			$m2 = ($m2 - (8 * 60 + 30)) / 60;
		}else{
			$m2 = ($m2 - (8 * 60 + 30) - (1.5 * 60)) / 60;
		}

		$t = ($m2 - $m1) < 0? ($m2 - $m1 + 8) : ($m2 - $m1);
		$d = floor((strtotime($endT) - strtotime($startT)) / (60 * 60 * 24)) * 8;
		return $t + $d;
	}

?>