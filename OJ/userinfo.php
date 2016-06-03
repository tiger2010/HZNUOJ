<?php
  /**
   * This file is modified
   * by yybird
   * @2016.04.26
  **/
?>

<?php
  $cache_time=10; 
  $OJ_CACHE_SHARE=false;
  require_once('./include/cache_start.php');
  require_once('./include/db_info.inc.php');
  require_once('./include/setlang.php');
  require_once("./include/const.inc.php");
  require_once("./include/my_func.inc.php");
 
  // check user
  $user=$_GET['user'];
  if (!is_valid_user_name($user)){
    echo "No such User!";
    exit(0);
  }
 
  $view_title=$user ."@".$OJ_NAME;
  $user_mysql=mysql_real_escape_string($user);

  $sql="SELECT `school`,`email`,`nick`,level,color,strength,real_name,class FROM `users` WHERE `user_id`='$user_mysql'";
  $result=mysql_query($sql);
  $row_cnt=mysql_num_rows($result);
  if ($row_cnt==0){ 
    $view_errors= "No such User!";
    require("template/".$OJ_TEMPLATE."/error.php");
    exit(0);
  }

  $row=mysql_fetch_object($result);
  $school=$row->school;
  $email=$row->email;
  $nick=$row->nick;
  $real_name = $row->real_name;
  $class = $row->class;
            if ($class=="null") $class = "其它";
            if ($class=="cs151") $class = "计算机151";
            if ($class=="cs152") $class = "计算机152";
            if ($class=="cs153") $class = "计算机153";
            if ($class=="cs154") $class = "计算机154";
            if ($class=="se151") $class = "软件工程151";
            if ($class=="se152") $class = "软件工程152";
            if ($class=="iot151") $class = "物联网151";
            if ($class=="cs141") $class = "计算机141";
            if ($class=="cs142") $class = "计算机142";
            if ($class=="cs143") $class = "计算机143";
            if ($class=="cs144") $class = "计算机144";
            if ($class=="se141") $class = "软件工程141";
            if ($class=="se142") $class = "软件工程142";
            if ($class=="iot141") $class = "物联网141";
  mysql_free_result($result);
 
  // 获取解题数大于10的用户数量存入user_cnt_divisor
  $sql = "SELECT user_id FROM users WHERE solved>10";
  $result  = mysql_query($sql) or die(mysql_error());
  if($result) $user_cnt_divisor = mysql_num_rows($result);
  else $user_cnt_divisor = 1;
  mysql_free_result($result);
//  echo $user_cnt_divisor;

  // count hznuoj solved
  $sql="SELECT count(DISTINCT problem_id) as ac FROM solution WHERE user_id='".$user_mysql."' AND result=4";
  $result=mysql_query($sql) or die(mysql_error());
  $row=mysql_fetch_object($result);
  $AC=$row->ac;
  mysql_free_result($result);

  // count hznuoj submission
  $sql="SELECT count(solution_id) as `Submit` FROM `solution` WHERE `user_id`='".$user_mysql."'";
  $result=mysql_query($sql) or die(mysql_error());
  $row=mysql_fetch_object($result);
  $Submit=$row->Submit;
  mysql_free_result($result);

  // 获取该用户AC的所有题目，存入result
  $sql = "SELECT DISTINCT problem_id FROM solution WHERE user_id='$user_mysql' AND result=4";
  $result = mysql_query($sql) or die(mysql_error());

  // 获取该用户AC的题目数量，存入rows_cnt
  if($result) $rows_cnt = mysql_num_rows($result);
  else $rows_cnt = 0;

  $strength = 0;
  $level = "斗之气一段";
  $color = "#E0E0E0";

  // 对于每道AC的题目，计算其分数，并加至strength
  $hznu_solved_set = array();
  for ($j=0; $j<$rows_cnt; $j++) {
    $row = mysql_fetch_object($result);
    $prob_id = $row->problem_id;
    $hznu_solved_set[] = $row->problem_id;
    $sql = "SELECT solved_user, submit_user FROM problem WHERE problem_id=".$prob_id;
    $y_result = mysql_query($sql) or die(mysql_error());
    $y_row = mysql_fetch_object($y_result);
    $solved = $y_row->solved_user;
    $submit = $y_row->submit_user;
    $scores = 100.0 * (1-($solved+$submit/2.0)/$user_cnt_divisor);
    if ($scores < 10) $scores = 10;
    $strength += $scores;
    mysql_free_result($y_result);
  }
  mysql_free_result($result);

  /* 查找HZNUOJ未解决的题目编号 start */
  $hznu_unsolved_set = array();
  $sql = "SELECT DISTINCT problem_id FROM solution WHERE user_id='$user_mysql' AND problem_id NOT IN (SELECT DISTINCT problem_id FROM solution WHERE user_id='$user_mysql' AND result=4)";
  $result = mysql_query($sql);
  for ($i=0; $row=mysql_fetch_array($result); ++$i) {
    $hznu_unsolved_set[$i] = $row['problem_id'];
  }
  mysql_free_result($result);
  /* 查找HZNUOJ未解决的题目编号 end */





  /* VJ计算部分 start */
  // 连接转入vjudge
  $connvj = mysql_connect($DB_VJHOST,$DB_VJUSER,$DB_VJPASS,true);
  if (!$connvj) die('Could not connect: ' . mysql_error());
  mysql_select_db("vhoj", $connvj);
  mysql_query("set names utf8");

  // get AC number and problem ID from all OJs
  // ZOJ
  $sql = "SELECT DISTINCT C_ORIGIN_PROB, C_PROBLEM_ID FROM t_submission WHERE C_ORIGIN_OJ='ZOJ' AND C_STATUS='Accepted' AND C_USERNAME='".$user_mysql."'";
  $result = mysql_query($sql) or die(mysql_error());
  $ZJU = mysql_num_rows($result);
  $zju_solved_set = array();
  $zju_vj_id = array(); // 映射vj上的ID
  for ($j=0; $j<$ZJU; $j++) {
    $row = mysql_fetch_object($result);
    $zju_solved_set[] = $row->C_ORIGIN_PROB;
    $zju_vj_id[$row->C_ORIGIN_PROB] = $row->C_PROBLEM_ID;
  }
  mysql_free_result($result);

  // HDOJ
  $sql = "SELECT DISTINCT C_ORIGIN_PROB, C_PROBLEM_ID FROM t_submission WHERE C_ORIGIN_OJ='HDU' AND C_STATUS='Accepted' AND C_USERNAME='".$user_mysql."'";
  $result = mysql_query($sql) or die(mysql_error());
  $HDU = mysql_num_rows($result);
  $hdu_solved_set = array();
  $hdu_vj_id = array(); // 映射vj上的ID
  for ($j=0; $j<$HDU; $j++) {
    $row = mysql_fetch_object($result);
    $hdu_solved_set[] = $row->C_ORIGIN_PROB;
    $hdu_vj_id[$row->C_ORIGIN_PROB] = $row->C_PROBLEM_ID;
  }
  mysql_free_result($result);

  // POJ
  $sql = "SELECT DISTINCT C_ORIGIN_PROB, C_PROBLEM_ID FROM t_submission WHERE C_ORIGIN_OJ='POJ' AND C_STATUS='Accepted' AND C_USERNAME='".$user_mysql."'";
  $result = mysql_query($sql) or die(mysql_error());
  $PKU = mysql_num_rows($result);
  $pku_solved_set = array();
  $pku_vj_id = array(); // 映射vj上的ID
  for ($j=0; $j<$PKU; $j++) {
    $row = mysql_fetch_object($result);
    $pku_solved_set[] = $row->C_ORIGIN_PROB;
    $pku_vj_id[$row->C_ORIGIN_PROB] = $row->C_PROBLEM_ID;
  }
  mysql_free_result($result);  

  // UVA
  $sql = "SELECT DISTINCT C_ORIGIN_PROB, C_PROBLEM_ID FROM t_submission WHERE C_ORIGIN_OJ='UVA' AND C_STATUS='Accepted' AND C_USERNAME='".$user_mysql."'";
  $result = mysql_query($sql) or die(mysql_error());
  $UVA = mysql_num_rows($result);
  $uva_solved_set = array();
  $uva_vj_id = array(); // 映射vj上的ID
  for ($j=0; $j<$UVA; $j++) {
    $row = mysql_fetch_object($result);
    $uva_solved_set[] = $row->C_ORIGIN_PROB;
    $uva_vj_id[$row->C_ORIGIN_PROB] = $row->C_PROBLEM_ID;
  }
  mysql_free_result($result);  

  // Codeforces
  $sql = "SELECT DISTINCT C_ORIGIN_PROB, C_PROBLEM_ID FROM t_submission WHERE C_ORIGIN_OJ='CodeForces' AND C_STATUS='Accepted' AND C_USERNAME='".$user_mysql."'";
  $result = mysql_query($sql) or die(mysql_error());
  $CF = mysql_num_rows($result);
  $cf_solved_set = array();
  $cf_vj_id = array(); // 映射vj上的ID
  for ($j=0; $j<$CF; $j++) {
    $row = mysql_fetch_object($result);
    $cf_solved_set[] = $row->C_ORIGIN_PROB;
    $cf_vj_id[$row->C_ORIGIN_PROB] = $row->C_PROBLEM_ID;
  }
  mysql_free_result($result);  

  // 获取vjudge用户数存入user_cnt_divisor
  $sql = "SELECT C_USERNAME FROM t_user";
  $result  = mysql_query($sql) or die(mysql_error());
  if($result) $user_cnt_divisor = mysql_num_rows($result);
  else $user_cnt_divisor = 1;
  mysql_free_result($result);
//  echo $user_cnt_divisor;

  // 获取该用户AC的所有题目，存入result
  $sql = "SELECT DISTINCT C_PROBLEM_ID FROM t_submission WHERE C_USERNAME='".$user_mysql."' AND C_STATUS='Accepted'";
  $result = mysql_query($sql) or die(mysql_error());

  // 获取该用户AC的题目数量，存入rows_cnt
  if($result) $rows_cnt = mysql_num_rows($result);
  else $rows_cnt = 0;

  // 对于每道AC的题目，计算其分数，并加至strength
  for ($j=0; $j<$rows_cnt; $j++) {
    // 获取题号
    $row = mysql_fetch_object($result);
    $prob_id = $row->C_PROBLEM_ID;
    // 获取AC人数
    $sql = "SELECT COUNT(DISTINCT C_USER_ID) AS ac_user FROM t_submission WHERE C_PROBLEM_ID=".$prob_id." AND C_STATUS='Accepted'";
    $y_result = mysql_query($sql) or die(mysql_error());
    $y_row = mysql_fetch_object($y_result);
    $solved = $y_row->ac_user;
    // 获取提交人数
    $sql = "SELECT COUNT(DISTINCT C_USER_ID) AS sub_user FROM t_submission WHERE C_PROBLEM_ID=".$prob_id;
    $submit = $y_row->sub_user;
    $scores = 100.0 * (1-($solved+$submit/2.0)/$user_cnt_divisor);
    if ($scores < 10) $scores = 10;
    $strength += $scores;
    mysql_free_result($y_result);
  }
  mysql_free_result($result);
  /* VJ计算部分 start */





  // 连接转回hustoj
  $conn = mysql_connect($DB_HOST,$DB_USER,$DB_PASS,true);
  if (!$conn) die('Could not connect: ' . mysql_error());
  mysql_select_db("jol", $conn);
  mysql_query("set names utf8");

  require_once("./include/rank.inc.php");

  // 根据数组计算该实力对应的等级和颜色
  if ($strength > $max_strength) {
    $color = "#6C3365";
    $level = "斗战胜佛";
  } else for ($j=1; $j<$level_total; $j++) {

    if ($strength < $level_strength[$j]) {
      $level = $level_name[$j-1];
      $color = $level_color[$j-1];
      break;
    }
  }
  
  // 更新用户信息
  $sql="UPDATE users SET solved=".$AC.",submit=".$Submit.",level='".$level."',strength=".$strength.",color='".$color."',ZJU=".$ZJU.",HDU=".$HDU.",PKU=".$PKU.",UVA=".$UVA.",CF=".$CF." WHERE user_id='".$user_mysql."'";
  $result=mysql_query($sql);

  // 获取排名
  $sql="SELECT count(*) as `Rank` FROM `users` WHERE strength>".round($strength,2);
  $result=mysql_query($sql);
  $row=mysql_fetch_array($result);
  $Rank=intval($row[0])+1;





  /* 计算图表相关信息 start */
  $total_solved = $AC+$CF+$HDU+$PKU+$UVA+$ZJU;

  // 计算总解题量的解题分
  $sql = "SELECT MAX(solved+CF+HDU+PKU+ZJU+UVA) FROM users";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);
  $max_solved = intval($row[0]);
  $solved_score = round(100.0*$total_solved/$max_solved); // 解题分
  mysql_free_result($result);

  // 计算平均难度分
  $dif_score = round(1.0*$strength/$total_solved); 

  // 计算活跃度分
  $AC_day = 0; // A过题目的天数
  $sub_day = 0; // 交过题目的天数
  $sql = "SELECT * FROM solution WHERE user_id='$user_mysql' ORDER BY in_date";
  $result = mysql_query($sql);
  $last_AC_time = 0; // 上一次AC的时间
  $last_sub_time = 0; // 上一次提交的时间
  $offset = strtotime("2012-01-01"); // 设置一个参考时间，若距离此时间的天数相等，则为同一天，否则不是同一天
  $day_sec = 60*60*24; // 一天的秒数
  while ($row = mysql_fetch_array($result)) {
    if (floor((strtotime($row['in_date'])-$offset)/$day_sec) != floor(($last_sub_time-$offset)/$day_sec)) { // 和上次提交不是同一天
      $sub_day++;
    }
    if ($row['result']==4) {
      if (floor((strtotime($row['in_date'])-$offset)/$day_sec) != floor(($last_AC_time-$offset)/$day_sec)) { // 计算有AC的天数
        $AC_day++;
      }
      $last_AC_time = strtotime($row['in_date']);
    }
    $last_sub_time = strtotime($row['in_date']);
  }
  mysql_free_result($result);
  // 更新数据
  $sql = "SELECT * FROM users_cache WHERE user_id='$user_id'";
  $result = mysql_query($sql);
  $result_num = mysql_num_rows($result);
  mysql_free_result($result);
  if ($result_num) { // 如果表中已存在该user的信息，直接更新
    $sql = "UPDATE users_cache SET class='$stu->class', AC_day=$AC_day, sub_day=$sub_day WHERE user_id='$user_id'";
    mysql_query($sql);
  } else { // 否则插入
    $sql = "INSERT INTO users_cache(user_id, class, AC_day, sub_day) VALUES ('$user_id', '$stu->class', $AC_day, $sub_day)";
    mysql_query($sql);
  }
  // 查找最大活跃度
  $sql = "SELECT MAX(AC_day) AS max FROM users_cache";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);
  if ($row['max']) $act_score = round(100.0*$AC_day/$row['max']);
  else $act_score = 0;

  // 计算抄袭分
  // 获取该用户所有AC的提交
  $sql = "SELECT sim 
          FROM 
            sim RIGHT JOIN (
              SELECT solution_id
              FROM solution
              WHERE result=4 AND user_id='$user_mysql'
            ) AS s 
            ON sim.s_id=s.solution_id";
  $result = mysql_query($sql, $conn);
  $copy_sum = 0; // sim和
  $AC_num = mysql_num_rows($result); // AC数
  // 逐个查看每个提交是否为抄袭
  while ($row = mysql_fetch_array($result)) {
    $copy_sum += $row['sim'];
  }
  mysql_free_result($result);
  if ($AC_num) $idp_score = 100-round(1.0*$copy_sum/$AC_num);
  else $idp_score = 0;

  // 计算总分
  $avg_score = round($solved_score*0.4+$dif_score*0.2+$act_score*0.2+$idp_score*0.2);
  /* 计算图表相关信息 end */





  if (isset($_SESSION['administrator'])){
    $sql="SELECT * FROM `loginlog` WHERE `user_id`='$user_mysql' order by `time` desc LIMIT 0,10";
    $result=mysql_query($sql) or die(mysql_error());
    $view_userinfo=array();
    $i=0;
    for (;$row=mysql_fetch_row($result);){
      $view_userinfo[$i]=$row;
      $i++;
    }
    echo "</table>";
    mysql_free_result($result);
  }

  $sql="SELECT result,count(1) FROM solution WHERE `user_id`='$user_mysql' AND result>=4 group by result order by result";
  $result=mysql_query($sql);
  $view_userstat=array();
  $i=0;
  while($row=mysql_fetch_array($result)){
    $view_userstat[$i++]=$row;
  }
  mysql_free_result($result);

  $sql= "SELECT UNIX_TIMESTAMP(date(in_date))*1000 md,count(1) c FROM `solution` where  `user_id`='$user_mysql' group by md order by md desc";
  $result=mysql_query($sql);//mysql_escape_string($sql));
  $chart_data_all= array();
  //echo $sql;
    
  while ($row=mysql_fetch_array($result)){
    $chart_data_all[$row['md']]=$row['c'];
  }
    
  $sql= "SELECT UNIX_TIMESTAMP(date(in_date))*1000 md,count(1) c FROM `solution` where  `user_id`='$user_mysql' and result=4 group by md order by md desc ";
  $result=mysql_query($sql);//mysql_escape_string($sql));
  $chart_data_ac= array();
  //echo $sql;
    
  while ($row=mysql_fetch_array($result)){
    $chart_data_ac[$row['md']]=$row['c'];
  }
  
  mysql_free_result($result);


  /* 获取HZNUOJ推荐题目的题目编号 start */
  $hznu_recommend_set = array();
  $sql = "SELECT DISTINCT problem_id FROM problem WHERE score<=$dif_score+5 AND score>=$dif_score-5 ORDER BY problem_id";
  $result = mysql_query($sql);
  for ($i=0; $row=mysql_fetch_array($result); ++$i) {
    $hznu_recommend_set[$i] = $row['problem_id'];
  }
  mysql_free_result($result);
  /* 获取HZNUOJ推荐题目的题目编号 end */


  /////////////////////////Template
  require("template/".$OJ_TEMPLATE."/userinfo.php");
  /////////////////////////Common foot
  if(file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>

