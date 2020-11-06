<?php

ini_set('log_errors','on');  //ログを取るか
ini_set('error_log','php.log');  //ログの出力ファイルを指定
session_start(); //セッション使う

// 自分のHP
define("MY_HP", 500);
// モンスター達格納用
$monsters = array();
// クラス（設計図）の作成
class Monster{
  // プロパティ
  protected $name;
  protected $hp;
  protected $img;
  protected $attack;
  // コンストラクタ
  public function __construct($name, $hp, $img, $attack) {
    $this->name = $name;
    $this->hp = $hp;
    $this->img = $img;
    $this->attack = $attack;
  }
  // メソッド
  public function attack(){
    $attackPoint = $this->attack;
    if(!mt_rand(0,9)){ //10分の1の確率でモンスターのクリティカル
      $attackPoint *= 1.5;
      $attackPoint = (int)$attackPoint;
      $_SESSION['history'] .= $this->getName().'のクリティカルヒット!!<br>';
    }
    $_SESSION['myhp'] -= $attackPoint;
    $_SESSION['history'] .= $attackPoint.'ポイントのダメージを受けた！<br>';
  }
  // セッター
  public function setHp($num){
    $this->hp = filter_var($num, FILTER_VALIDATE_INT);
  }
  public function setAttack($num){
    $this->attack = (int)filter_var($num, FILTER_VALIDATE_FLOAT);
  }
  // ゲッター
  public function getName(){
    return $this->name;
  }
  public function getHp(){
    return $this->hp;
  }
  public function getImg(){
    return $this->img;
  }
  public function getAttack(){
    return $this->attack;
  }
}
// 魔法を使えるモンスタークラス
class MagicMonster extends Monster{
  private $magicAttack;
  function __construct($name, $hp, $img, $attack, $magicAttack) {
    // 親クラスのコンストラクタで処理する内容を継承したい場合には親コンストラクタを呼び出す。
    parent::__construct($name, $hp, $img, $attack);
    $this->magicAttack = $magicAttack;
  }
  public function getMagicAttack(){
    return $this->magicAttack;
  }
  // 魔法攻撃力が増えることはない前提として、セッターは作らない（読み取り専用）
//  public function magicAttack(){
//    $_SESSION['history'] .= $this->name.'の魔法攻撃!!<br>';
//    $_SESSION['myhp'] -= $this->magicAttack;
//    $_SESSION['history'] .= $this->magicAttack.'ポイントのダメージを受けた！<br>';
//  }
  // attackメソッドをオーバーライドすることで、「ゲーム進行を管理する処理側」は単にattackメソッドを呼べばいいだけになる
  // 魔法を使えるモンスターは、自分で魔法を出すか普通に攻撃するかを判断する
  public function attack(){
    $attackPoint = $this->attack;
    if(!mt_rand(0,4)){ //5分の1の確率で魔法攻撃
      $_SESSION['history'] .= $this->name.'の魔法攻撃!!<br>';
      $_SESSION['myhp'] -= $this->magicAttack;
      $_SESSION['history'] .= $this->magicAttack.'ポイントのダメージを受けた！<br>';
    }else{
      // 通常の攻撃の場合は、親クラスの攻撃メソッドを使うことで、親クラスの攻撃メソッドが修正されてもMagicMonsterでも反映される
      parent::attack();
    }
  }
}
// インスタンス生成
$monsters[] = new Monster( 'いたずらもぐら', 100, 'img/monster01.png', mt_rand(20, 40) );
$monsters[] = new MagicMonster( 'アルミラージ', 300, 'img/monster02.png', mt_rand(20, 60), mt_rand(50, 100) );
$monsters[] = new Monster( 'おおきづき', 200, 'img/monster03.png', mt_rand(30, 50) );
$monsters[] = new MagicMonster( 'スライム', 400, 'img/monster04.png', mt_rand(50, 80), mt_rand(60, 120) );
$monsters[] = new Monster( 'スライムベス', 150, 'img/monster05.png', mt_rand(30, 60) );
$monsters[] = new Monster( 'ドラキー', 100, 'img/monster06.png', mt_rand(10, 30) );
$monsters[] = new Monster( 'メラゴースト', 120, 'img/monster07.png', mt_rand(20, 30) );
$monsters[] = new Monster( 'マーモン', 180, 'img/monster08.png', mt_rand(30, 50) );

function createMonster(){
  global $monsters;
  $monster =  $monsters[mt_rand(0, 7)];
  $_SESSION['history'] .= $monster->getName().'が現れた！<br>';
  $_SESSION['monster'] =  $monster;
}
function init(){
  $_SESSION['history'] .= '初期化します！<br>';
  $_SESSION['knockDownCount'] = 0;
  $_SESSION['myhp'] = MY_HP;
  createMonster();
}
function gameOver(){
  $_SESSION = array();
}


//1.post送信されていた場合
if(!empty($_POST)){
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false;
  error_log('POSTされた！');

  if($startFlg){
    $_SESSION['history'] = 'ゲームスタート！<br>';
    init();
  }else{
    // 攻撃するを押した場合
    if($attackFlg){
      $_SESSION['history'] .= '攻撃した！<br>';
      // ランダムでモンスターに攻撃を与える
      $attackPoint = mt_rand(50,100);
      $_SESSION['monster']->setHp( $_SESSION['monster']->getHp() - $attackPoint );
      $_SESSION['history'] .= $attackPoint.'ポイントのダメージを与えた！<br>';

      // モンスターが攻撃をする
      $_SESSION['monster']->attack();

      // 自分のhpが0以下になったらゲームオーバー
      if($_SESSION['myhp'] <= 0){
        gameOver();
      }else{
        // hpが0以下になったら、別のモンスターを出現させる
        if($_SESSION['monster']->getHp() <= 0){
          $_SESSION['history'] .= $_SESSION['monster']->getName().'を倒した！<br>';
          createMonster();
          $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
        }
      }
    }else{ //逃げるを押した場合
      $_SESSION['history'] .= '逃げた！<br>';
      createMonster();
    }
  }
  $_POST = array();
}

?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>ホームページのタイトル</title>
    <style>
    	body{
	    	margin: 0 auto;
	    	padding: 150px;
	    	width: 25%;
	    	background: #fbfbfa;
        color: white;
    	}
    	h1{ color: white; font-size: 20px; text-align: center;}
      h2{ color: white; font-size: 16px; text-align: center;}
    	form{
	    	overflow: hidden;
    	}
    	input[type="text"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
      input[type="password"]{
    		color: #545454;
	    	height: 60px;
	    	width: 100%;
	    	padding: 5px 10px;
	    	font-size: 16px;
	    	display: block;
	    	margin-bottom: 10px;
	    	box-sizing: border-box;
    	}
    	input[type="submit"]{
	    	border: none;
	    	padding: 15px 30px;
	    	margin-bottom: 15px;
	    	background: black;
	    	color: white;
	    	float: right;
    	}
    	input[type="submit"]:hover{
	    	background: #3d3938;
	    	cursor: pointer;
    	}
    	a{
	    	color: #545454;
	    	display: block;
    	}
    	a:hover{
	    	text-decoration: none;
    	}
    </style>
  </head>
  <body>
   <h1 style="text-align:center; color:#333;">ドラゴンクエスト</h1>
    <div style="background:black; padding:15px; position:relative;">
      <?php if(empty($_SESSION)){ ?>
        <h2 style="margin-top:60px;">GAME START ?</h2>
        <form method="post">
          <input type="submit" name="start" value="▶ゲームスタート">
        </form>
      <?php }else{ ?>
        <h2><?php echo $_SESSION['monster']->getName().'が現れた!!'; ?></h2>
        <div style="height: 150px;">
          <img src="<?php echo $_SESSION['monster']->getImg(); ?>" style="width:120px; height:auto; margin:40px auto 0 auto; display:block;">
        </div>
        <p style="font-size:14px; text-align:center;">モンスターのHP：<?php echo $_SESSION['monster']->getHp(); ?></p>
        <p>倒したモンスター数：<?php echo $_SESSION['knockDownCount']; ?></p>
        <p>勇者の残りHP：<?php echo $_SESSION['myhp']; ?></p>
        <form method="post">
          <input type="submit" name="attack" value="▶攻撃する">
          <input type="submit" name="escape" value="▶逃げる">
          <input type="submit" name="start" value="▶ゲームリスタート">
        </form>
      <?php } ?>
      <div style="position:absolute; right:-350px; top:0; color:black; width: 300px;">
        <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
      </div>
    </div>

  </body>
</html>
