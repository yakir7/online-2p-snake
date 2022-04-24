<? 
$gamesize=8;
session_start();
$gid=$_GET['game'];
$join=$_GET['j'];
$filename="games/".$gid.".txt";
$isgame=file_exists($filename);
function gengid(){
	return substr(hash('ripemd160', rand(0, 999999999999999)), 0, 10);
}
function checkdir($data_, $head_, $chx, $chy){
	for ($s=0; $s<2; ++$s){
		for ($l=0; $l<sizeof($data_->snakes[$s]); ++$l){
			if (($data_->snakes[$s][$l][0]==$head_[0]+$chx && $data_->snakes[$s][$l][1]==$head_[1]+$chy)){
				return 1;
			}
		}
	}
	return 0;
}
if (!$isgame){
	session_destroy();
	session_start();
	if (!$gid || strlen($gid)!=10){
		$gid=gengid();
	}
	$meta = array('p1' => session_id(),'p2'=>'','buttons'=>array(0,0,0,0,0,0,0,0), "changeblocks"=>[]);
	$bsize=8;
	$snakes=[[[5,6],[5, 7],[6,7],[7,7]], //first snake, x y, start with head
			[[2,1],[2,0],[1,0],[0,0]]];
	file_put_contents("games/".$gid.".txt", json_encode(array(
		'meta'=>$meta,
		'player'=>0,
		'bsize'=>$bsize,
		'snakes'=>$snakes,
		'foods'=>[],
		'moves'=>0,
		'winner'=>0 //1= player1, 2= player2, 3= tie
	)));
	echo '<head><meta http-equiv="refresh" content="0;url=?game='.$gid.'" /></head><body>creating new game</body>';
	exit();
}else{
	$rawdata=file_get_contents($filename);
	$data=json_decode($rawdata);
	if ($data->meta->p1!=session_id()){
		if ($data->meta->p2==""){
			if ($join){
				$data->meta->p2=session_id();
				//$_SESSION["player"]=2;
				$player=2;
			}
			//echo "as player 2";
			//session_destroy();
			//session_start();
		}else if($data->meta->p2==session_id()){
			$player=2;
			//$_SESSION["player"]=2;
			//echo "playing as player 2";
		}else{
			//echo "room full ";
		}
	}else{
		$player=1;
		//$_SESSION["player"]=1;
		//echo "playing as player 1";
	}
	//$player=$_SESSION["player"];
	$data->player=$player;
	
	if ($_GET['rematch']){
		$data->meta->remgid = $_GET['rematch'];
	}
	$turn=false;
	if ($data->moves%2==0 && $player==2){
		$turn=true;
		//echo " your turn ";
	}elseif ($data->moves%2==1 && $player==1){
		$turn=true;
		//echo " your turn ";
	}
	//echo $_GET["movex"]."...".$_GET["movey"];
	$changeblocks=[];
	$tails=[ [ end($data->snakes[0])[0], end($data->snakes[0])[1] ], [ end($data->snakes[1])[0], end($data->snakes[1])[1] ] ];
	//echo json_encode($tails);
	if ($turn && abs($_GET["movex"]+$_GET["movey"])<2 && $data->winner==0){
		$snake=$data->snakes[$player-1];
		$head=[$snake[0][0]+$_GET["movex"], $snake[0][1]+$_GET["movey"]];
		$hit=false;
		if ($head[0]>=0 && $head[0]<$data->bsize && $head[1]>=0 && $head[1]<$data->bsize){

			$hit=checkdir($data, $head, 0, 0)==1?true:false;
			if (!$hit){
				array_push($changeblocks, array($head[0], $head[1], 1, $player));
				array_push($changeblocks, array(end($data->snakes[$player-1])[0], end($data->snakes[$player-1])[1], 0, $player));

				array_unshift($data->snakes[$player-1], $head);
				array_pop($data->snakes[$player-1]);
				$data->moves++;
				//echo " moved ";
				//check for a winner
				
				function checkwin($data_){
					$head1=$data_->snakes[0][0];
					$head2=$data_->snakes[1][0];

					$res[0]=$head1[1]==0?1:0;
					$res[1]=$head1[0]==$data_->bsize-1?1:0;
					$res[2]=$head1[1]==$data_->bsize-1?1:0;
					$res[3]=$head1[0]==0?1:0;
					$res[4]=$head2[1]==0?1:0;
					$res[5]=$head2[0]==$data_->bsize-1?1:0;
					$res[6]=$head2[1]==$data_->bsize-1?1:0;
					$res[7]=$head2[0]==0?1:0;
					
					$res[0]=$res[0]==1?1:checkdir($data_, $head1, 0, -1);
					$res[1]=$res[1]==1?1:checkdir($data_, $head1, 1, 0);
					$res[2]=$res[2]==1?1:checkdir($data_, $head1, 0, 1);
					$res[3]=$res[3]==1?1:checkdir($data_, $head1, -1, 0);
					$res[4]=$res[4]==1?1:checkdir($data_, $head2, 0, -1);
					$res[5]=$res[5]==1?1:checkdir($data_, $head2, 1, 0);
					$res[6]=$res[6]==1?1:checkdir($data_, $head2, 0, 1);
					$res[7]=$res[7]==1?1:checkdir($data_, $head2, -1, 0);
					return $res;
					
				}
				if(checkwin($data)==[1,1,1,1,1,1,1,1]){
					$data->winner=3;
					//echo " its a tie ";
				}elseif (array_slice(checkwin($data), 0, 4)==[1,1,1,1]&&array_slice(checkwin($data), 4)!=[1,1,1,1]){
					$data->winner=2;
					//echo " player 2 wins ";
				}elseif(array_slice(checkwin($data), 4)==[1,1,1,1]&&array_slice(checkwin($data), 0, 4)!=[1,1,1,1]){
					$data->winner=1;
					//echo " player 1 wins ";
				}
				$eaten=-1;
				for ($f=0; $f<sizeof($data->foods); ++$f){
					for ($sn=0; $sn<2; ++$sn){
						if ($eaten==-1 && $data->foods[$f][0]==$data->snakes[$sn][0][0] && $data->foods[$f][1]==$data->snakes[$sn][0][1]){
							$eaten=$f;
						}
					}
				}
				if ($eaten>-1){
					array_push($data->snakes[$player-1], $tails[$player-1]);
					array_splice($data->foods, $eaten, 1);
					//echo ' eat ';
				}
				//echo json_encode(checkwin($data));
				
			}
		}else{
			$hit=true;
		}
		if ($hit) {//echo " illegal move ";
		}
	}else if ($data->winner>0 && $data->winner<3){
		//echo "winner is player ".$data->winner;
	}else if ($data->winner==3){
		//echo "its a tie";
	}
	while (sizeof($data->foods)<3){
		$apple=[rand(0, $data->bsize-1),rand(0, $data->bsize-1)];
		$isfood=false;
		for ($i=0; $i<sizeof($data->foods); ++$i){
			if ($apple==$data->foods[$i]){
				$isfood=true;
			}
		}
		if (!checkdir($data, $apple, 0, 0) && !$isfood){
			array_push($data->foods, $apple);///increare to 2 and add food location check
			array_push($changeblocks, array($apple[0], $apple[1], 1, 3));
		}
	}
	//echo "<br>";
	function showButt($data_, $dir, $head){
		$show=true;
		if ($head[0]+$dir[0]>=0 && $head[0]+$dir[0]<$data_->bsize && $head[1]+$dir[1]>=0 && $head[1]+$dir[1]<$data_->bsize){
			if (checkdir($data_, $head, $dir[0], $dir[1])==1){
				$show=false;
			}
		}else{
			$show=false;
		}
		return $show;
	}
	$butt=[0,0,0,0,0,0,0,0];
	$butt[0]=showButt($data, [0,-1], $data->snakes[0][0])?[0,-1]:0;
	$butt[1]=showButt($data, [1, 0], $data->snakes[0][0])?[1, 0]:0;
	$butt[2]=showButt($data, [0, 1], $data->snakes[0][0])?[0, 1]:0;
	$butt[3]=showButt($data, [-1,0], $data->snakes[0][0])?[-1,0]:0;
	$butt[4]=showButt($data, [0,-1], $data->snakes[1][0])?[0,-1]:0;
	$butt[5]=showButt($data, [1, 0], $data->snakes[1][0])?[1, 0]:0;
	$butt[6]=showButt($data, [0, 1], $data->snakes[1][0])?[0, 1]:0;
	$butt[7]=showButt($data, [-1,0], $data->snakes[1][0])?[-1,0]:0;
	if ($player==1){
		array_splice ($butt , 4 ,4  );
	}else{
		array_splice ($butt , 0 ,4  );
	}
	if ($data->winner==0){$data->buttons=$butt;}
	//echo json_encode($data->buttons)." ".$player;//[1,1,0,0,1,0,1,1]
	//exit();
	$draw=0;
	for ($y=0; $y<$data->bsize; ++$y){
		for ($x=0; $x<$data->bsize; ++$x){
			for ($s=0; $s<2; ++$s){
				for ($l=0; $l<sizeof($data->snakes[$s]); ++$l){
					if ($data->snakes[$s][$l][0]==$x && $data->snakes[$s][$l][1]==$y){
						//echo "X,";
						$draw=1;
					}
				}
				for ($f=0; $f<sizeof($data->foods); ++$f){
					if (!$draw==1 && $data->foods[$f][0]==$x && $data->foods[$f][1]==$y){
						//echo "@,";
						$draw=1;
					}
				}
			}
			if ($draw==1) {$draw=0;} else {//echo "O,";
			}
		}
		//echo "<br>";
	}
	$data->changeblocks=$changeblocks;
	
	file_put_contents($filename, json_encode($data));
	if ($_GET['json']){
		echo json_encode($data);
		exit();
	}
}
?>
<head>
<title>Snake for 2 Players Online</title>
<meta name="Description" content="Invite a friend online for a 1v1 snake match (turn based)">
<link rel="icon" type="image/png" href="favicon.png" />
<style>
@media only screen and (orientation: landscape) {
	#canv {height: 90%;} 
	#instructions {font-size:200%;} }
@media only screen and (orientation: portrait) { 
	#canv {width: 90%;} #toplink {font-size:200%;}
	#newgame {font-size:200%;} #urlcopy {font-size:100%;}
	#copybtn {font-size:100%} }
body {font-family: "Arial";}
#giflink { display: none; }
#giflink, #copybtn, #newgame, #instructions, #remlink, #toplink, #urlcopy { background-color: #ffffaa; }
</style>
</head><body>
<center>
<? //echo session_id()."<br>".$_SESSION["player"]."<br>"; ?>
<? 
if (!$join && !$player){
	echo '<p><a href="?game=' . $gid . '&j=1"><h1>Join game as player 2<h1></a></p>';
	exit();
}
$remid = gengid();
?>
<span id="remlink" style="position:fixed; top:0px;left:50%;transform: translate(-50%, 0%);display:none;font-size:200%;">Game ended. <a href='#' onclick='refresh(_URL+"&rematch=<? echo $remid ?>&json=1")'>Offer Rematch?</a><br></span>
<div id="toplink" style="position:fixed; top:0px;left:50%;transform: translate(-50%, 0%);">send this url to a friend 
	<input id="copybtn" type="button" onclick="copyt('urlcopy')" value="COPY"><br> 
<input  id="urlcopy" type="text" name="country" value="<?= 'http://'.$_SERVER['HTTP_HOST'].strtok($_SERVER['REQUEST_URI'], '?')."?game=".$gid ?>" size="40" readonly>
<br><span id="instructions" style="font-weight: bold;">instructions: block the other player</span>
<br>waiting for player 2...</div>

<canvas id="canv" width=200 height=200 ></canvas><br>
<span id="giflink" style="position:fixed; bottom:5%;left:50%;transform: translate(-50%, 0%);">gameplay GIF <a style="background-color: #FFFF00" href="javascript:encoder.download('gameplay.gif');">download</a><br></span>
<span id="newgame" style="position:fixed; bottom:0px;left:50%;transform: translate(-50%, 0%);"> create a <a href="./">new game</a><br></span>
<script type="text/javascript" src="jsgif/LZWEncoder.js"></script>
<script type="text/javascript" src="jsgif/NeuQuant.js"></script>
<script type="text/javascript" src="jsgif/GIFEncoder.js"></script>
<script type="text/javascript" src="jsgif/b64.js"></script>
<script>
//document.getElementById("giflink").innerHTML = "<a href=\"javascript:encoder.download('gameplay.gif');\">dfgdfg</a>";

function copyt(el) {
  var copyText = document.getElementById(el);
  copyText.select(); 
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/
  document.execCommand("copy");
  //alert("Copied the text: " + copyText.value);
}

function Get(yourUrl){
	var Httpreq = new XMLHttpRequest(); // a new request
	Httpreq.open("GET",yourUrl,false);
	Httpreq.send(null);
	return Httpreq.responseText;          
}
refresh=(_url)=>{
	//console.log("refresh");
	tdata = Get(_url !== undefined ? _url : _URL+"&json=1" );
	if (tdata.search("bsize")>1){
		//console.log("yes");
		data = JSON.parse(tdata);
	}else{
		console.log("cant load data");
	}

	//console.log(data);
	if (_url != undefined && _url.indexOf("rematch")>0){
		window.location.href = _URL2+"?game="+data.meta.remgid;
	}
	if (data.meta.p1!="" && data.meta.p2!=""){
		toplink1.style.display = "none";
		//innerHTML = "";
	}
	draw(ctx, data, wh);

	if (data.winner>0 && data.winner != data.player){ 
		if ((data.winner==3 && data.player==1)||data.winner<3){
			//clearInterval(ref)
			//toplink1.innerHTML = "";
			remlink1.style.display = "block";
			//newgame1.style.display = "block";
		}
 	}
	if (data.meta.remgid){ 
	    remlink1.innerHTML="Rematch Offer received. <a href='"+_URL2+"?game="+data.meta.remgid+"&join=1'>Accept?</a>";
	    remlink1.style.display = "block";
	}
	//
}
function MP(canvas, event) { 
    let rect = canvas.getBoundingClientRect(); 
    let x = (event.clientX - rect.left); 
    let y = (event.clientY - rect.top); 
    return [x,y];//
} 
press=(e)=>{
	let rect = canvas.getBoundingClientRect(); 
	let _wh=rect.width;
	if (data.winner==0){
	    var pos=MP(canvasElem, e);
	    var url=checkbtn(data,pos,_wh);
	    if (url){
	    	//var url=_URL+"&movex="+data.buttons[i][0]+"&movey="+data.buttons[i][1]+"&json=1";
	    	//var url=`${_URL}&movex=${data.buttons[i][0]}&movey=${data.buttons[i][1]}&json=1`;
	    	refresh(url);
	    }
	  //   for (var i=0; i<4; i++){
	  //   	var x=data.snakes[data.player-1][0][0]+data.buttons[i][0];
			// var y=data.snakes[data.player-1][0][1]+data.buttons[i][1];
	  //   	if ( x == Math.floor( pos[0]/(_wh/data.bsize) ) && y == Math.floor( pos[1]/(_wh/data.bsize) ) ){
	  //   		var url=_URL+"&movex="+data.buttons[i][0]+"&movey="+data.buttons[i][1]+"&json=1";
	  //   		refresh(url);
			// }
	  //   }
	}
}
move=(e)=>{
	let _wh=canvas.getBoundingClientRect().width;
	if (data.winner==0) canvas.style.cursor=checkbtn(data,MP(canvasElem, e),_wh)?"pointer":"auto";
}
function checkbtn(data,pos,wh){
	for (var i=0; i<4; i++){
		if ((data.moves%2==0 && data.player==2)||(data.moves%2==1 && data.player==1)){
	    	var x=data.snakes[data.player-1][0][0]+data.buttons[i][0];
			var y=data.snakes[data.player-1][0][1]+data.buttons[i][1];
	    	if ( x == Math.floor( pos[0]/(wh/data.bsize) ) && y == Math.floor( pos[1]/(wh/data.bsize) ) ){
	    		return `${_URL}&movex=${data.buttons[i][0]}&movey=${data.buttons[i][1]}&json=1`;
			}
		}
    }
    return false;
}
function circle(ctx, cenx, ceny, radius, color){
	var fs=ctx.fillStyle;
	ctx.fillStyle = color;
	ctx.beginPath();
	ctx.arc(cenx, ceny, radius, 0, 2 * Math.PI);//0.5
	ctx.closePath();
	ctx.fill();
	ctx.fillStyle=fs;
}
function draw(ctx, data, wh){
	var {bsize, snakes, foods, buttons, player, moves, winner} = data;
	var cw=wh/bsize;
	///ctx.clearRect(0,0,wh,wh);
	ctx.fillStyle = clr[7];
	ctx.fillRect(0, 0, wh, wh);
	ctx.fillStyle = clr[0];
	for (var y=0; y<bsize; ++y){
		for (var x=0; x<bsize; ++x){
			ctx.fillRect(x*cw+gap[0], y*cw+gap[0], cw-gap[0]*2, cw-gap[0]*2);
		}
	}
	
	for (var snake=0; snake<2; ++snake){
		var _snk=snakes[snake];
		
		ctx.fillStyle = (snake==0)?clr[1]:clr[2];
		for (var cell=0; cell<_snk.length; ++cell){
			var _cell=_snk[cell];
			var _x=_cell[0]*cw;
			var _y=_cell[1]*cw;
			var _path=[];
			var _gapc=[0,0,0,0];
			//ctx.fillRect(_x+gap[1], _y+gap[1], cw-gap[1]*2, cw-gap[1]*2);
			if (cell==0){
				ctx.fillRect(_x+gap[1]-1, _y+gap[1]-1, cw-gap[1]*2, cw-gap[1]*2);
				var eyes=[];
				if (_snk[1][1]==(_snk[0][1]-1)){
					eyes=[ 0.3, 0.7,0.7, 0.7];
					_gapc=[1,0,0,0];
				}else if (_snk[1][1]==(_snk[0][1]+1)){
					eyes=[ 0.3, 0.3, 0.7, 0.3];
					_gapc=[0,0,1,0];
				}else if (_snk[1][0]==(_snk[0][0]+1)){
					eyes=[ 0.3, 0.3,0.3, 0.7];
					_gapc=[0,1,0,0];
				}else if (_snk[1][0]==(_snk[0][0]-1)){
					eyes=[ 0.7, 0.3,0.7, 0.7];
					_gapc=[0,0,0,1];
				}
				circle(ctx, _x+cw*eyes[0], _y+cw*eyes[1], cw/6, clr[5]);
				circle(ctx, _x+cw*eyes[2], _y+cw*eyes[3], cw/6, clr[5]);
				ctx.fillStyle = clr[6];
				ctx.fillRect(_x+cw*eyes[0], _y+cw*eyes[1], 2, 2);
				ctx.fillRect(_x+cw*eyes[2], _y+cw*eyes[3], 2, 2);
				ctx.fillStyle = (snake==0)?clr[1]:clr[2];
			}else if(cell!=_snk.length-1){
				
				if ((_snk[cell-1][0]!=_snk[cell+1][0])&&(_snk[cell-1][1]!=_snk[cell+1][1])){
					var links=[_snk[cell-1][0]-_cell[0], _snk[cell-1][1]-_cell[1],
					   _snk[cell+1][0]-_cell[0], _snk[cell+1][1]-_cell[1]];//JSON.stringify(links);
				     var l=[[-1,0,0,1],[0,1,-1,0],
				    			[1,0,0,1],[0,1,1,0],
				    			[1,0,0,-1],[0,-1,1,0],
				    			[-1,0,0,-1],[0,-1,-1,0]];
				    var _links=JSON.stringify(links);
				   // console.log(_links);
					var an=[1.5,0, 1,1.5, 0.5,1, 0,0.5];
					_path=[null,null,null,null,null,null,null,null,null];
					
					for (var i=0; i<8; i+=2){
						//if (alllinks[i]==_links || alllinks[i+1]==_links){
						if (_links==JSON.stringify(l[i])||_links==JSON.stringify(l[i+1])){
							//console.log("match: "+_links+" "+an[i]);
							_path[6]=an[i];
						 	_path[7]=an[i+1];
						 }
					}

					if (_path[6]==1.5) {
						//console.log();
						_path=[_x+cw-gap[1]*2,_y+cw-gap[1]*2,
							   _x+gap[1],_y+cw-gap[1]*2,
							   _x+gap[1],_y+gap[1],
							   //_x+gap[1],_y+cw-gap[1]*2,
							   _path[6], _path[7]];
						_gapc=[0,0,1,1];
					}else if (_path[6]==1) {
						_path=[_x+cw-gap[1]*2,_y+gap[1],
							   _x+cw-gap[1]*2,_y+cw-gap[1]*2,
							   _x+gap[1],_y+cw-gap[1]*2,
							   _path[6], _path[7]];
						_gapc=[0,1,1,0];

					}else if (_path[6]==0) {
						_path=[_x+gap[1],_y+cw-gap[1]*2,
							   _x+gap[1],_y+gap[1],
							   _x+cw-gap[1]*2,_y+gap[1],
							   _path[6], _path[7]];
						_gapc=[1,0,0,1];

					}else if (_path[6]==0.5) {
						_path=[_x+gap[1],_y+gap[1],
							   _x+cw-gap[1]*2,_y+gap[1],
							   _x+cw-gap[1]*2,_y+cw-gap[1]*2,
							   _path[6], _path[7]];
						 _gapc=[1,1,0,0];

					}
					if (_path[0]!=null){
						ctx.beginPath();
						ctx.moveTo(_path[0], _path[1]);
						ctx.lineTo(_path[2], _path[3]);
						ctx.lineTo(_path[4], _path[5]);
						ctx.arc(_path[2], _path[3],cw-gap[1]*2-1, _path[6]*Math.PI, _path[7]*Math.PI);//0.5
						ctx.closePath();
						ctx.fill();
					}
					
					//ctx.fillRect(_x+gap[1], _y+gap[1], cw-gap[1]*2, cw-gap[1]*2);
				}else if(_snk[cell-1][0]==_snk[cell+1][0]){
					ctx.fillRect(_x+gap[1]-1, _y, cw-gap[1]*2, cw);
					//console.log("asdasdsad");
				}else if(_snk[cell-1][1]==_snk[cell+1][1]){
					ctx.fillRect(_x, _y+gap[1]-1, cw, cw-gap[1]*2);
					//console.log("asdasdsad");
				}else{
					ctx.fillRect(_x+gap[1], _y+gap[1], cw-gap[1]*2, cw-gap[1]*2);
				}
					//ctx.fillRect(_x+gap[1], _y+gap[1], cw-gap[1]*2, cw-gap[1]*2);
				
			}else{
				var _tail=[];
				if(_snk[cell][0]==_snk[cell-1][0]+1){
					_tail=[_x,_y+gap[1],
						   _x+cw-gap[1]*2,_y+(cw-gap[1]*2)/2,
						   _x,_y+cw-gap[1]*2]
				}else if(_snk[cell][0]==_snk[cell-1][0]-1){
					_tail=[_x+cw,_y+gap[1],
						   _x+gap[1],_y+(cw-gap[1]*2)/2+1,
						   _x+cw,_y+cw-gap[1]*2+1]
				}else if(_snk[cell][1]==_snk[cell-1][1]+1){
					_tail=[_x+gap[1],_y,
						   _x+(cw-gap[1]*2)/2,_y+cw-gap[1]*2,
						   _x+cw-gap[1]*2,_y]
				}else if(_snk[cell][1]==_snk[cell-1][1]-1){
					_tail=[_x+gap[1],_y+cw,
						   _x+(cw-gap[1]*2)/2,_y+gap[1],
						   _x+cw-gap[1]*2,_y+cw]
				}else{
					ctx.fillRect(_x+gap[1], _y+gap[1], cw-gap[1]*2, cw-gap[1]*2);
				}
				if (_tail.length>0){
					ctx.beginPath();
					ctx.moveTo(_tail[0],_tail[1]);
					ctx.lineTo(_tail[2],_tail[3]);
					ctx.lineTo(_tail[4],_tail[5]);
					ctx.closePath();
					ctx.fill();
				}
			}
			if (_gapc[0]==1){
				ctx.fillRect(_x+gap[1]-1, _y, cw-gap[1]*2, gap[1]+2);
			}
			if (_gapc[1]==1){
				ctx.fillRect(_x+cw-gap[1]-2, _y+gap[1]-1, gap[1]+2, cw-gap[1]*2);
			}
			if (_gapc[2]==1){
				ctx.fillRect(_x+gap[1]-1, _y+cw-gap[1]-2, cw-gap[1]*2, gap[1]+2);
			}
			if (_gapc[3]==1){
				ctx.fillRect(_x, _y+gap[1]-1, gap[1]+2, cw-gap[1]*2);
			}


		}
	}
	ctx.lineWidth=5;

	//easy draw snake
	/*snakes.map((snake, si)=>{ 
		//ctx.strokeStyle = (si==0)?clr[1]:clr[2];
		ctx.strokeStyle="#000000";
		snake.map((cell, ci)=>{
		if (ci==0){ 
			ctx.beginPath();
			ctx.lineJoin = "round";
			ctx.lineCap = "round";
			ctx.moveTo( cell[0]*cw+cw/2, cell[1]*cw+cw/2) 
		}else {
			ctx.lineTo( cell[0]*cw+cw/2, cell[1]*cw+cw/2) 
			if (ci==snake.length-1) ctx.stroke();		
		}
		//snake
	}) });*/
	for (var food=0; food<foods.length; ++food){
		//circle(ctx, foods[food][0]*cw+cw/2, foods[food][1]*cw+cw/2, gap[2], clr[3]);
	}
	foods.map((food)=>{
		circle(ctx, food[0]*cw+cw/2, food[1]*cw+cw/2, gap[2], clr[3]);})
	//gif
	if(winner>0 && anim_moves<999999 && anim_moves>0){
		encoder.addFrame(ctx);
		encoder.addFrame(ctx);
		encoder.addFrame(ctx);
		encoder.finish();
		document.getElementById("giflink").style.display = "block";
		anim_moves=1000000;
	}else if (anim_moves>=0 && anim_moves<moves && winner==0){
		anim_moves=moves;
		encoder.addFrame(ctx);
	}else if(anim_moves==-1  && winner==0){
		anim_moves=0;
		encoder.start();
	}//
	if (winner==0&&((moves%2==0 && player==2)||(moves%2==1 && player==1))){
		ctx.fillStyle = clr[4];
		for (var i=0; i<4; ++i){
			if (Array.isArray(buttons[i])){
				var _b=buttons[i];
				//var x=;
				//var y=;
				var _mid=[(snakes[player-1][0][0]+_b[0])*cw+cw/2, 
						  (snakes[player-1][0][1]+_b[1])*cw+cw/2];
				var _path=[];
				ctx.beginPath();
				if (_b[0]==0 && _b[1]==-1){
					_path=[_mid[0]-gap[3], _mid[1]+gap[3],_mid[0],_mid[1]-gap[3],_mid[0]+gap[3],_mid[1]+gap[3]];
				}else if (_b[0]==0 && _b[1]==1){
					_path=[_mid[0]-gap[3], _mid[1]-gap[3],_mid[0],_mid[1]+gap[3],_mid[0]+gap[3],_mid[1]-gap[3]];
				}else if (_b[0]==1 && _b[1]==0){
					_path=[_mid[0]-gap[3], _mid[1]-gap[3],_mid[0]+gap[3], _mid[1],_mid[0]-gap[3],_mid[1]+gap[3]];
				}else if (_b[0]==-1 && _b[1]==0){
					_path=[_mid[0]+gap[3],_mid[1]-gap[3],_mid[0]-gap[3],_mid[1],_mid[0]+gap[3],_mid[1]+gap[3]];
				}
				ctx.moveTo(_path[0], _path[1]);
				ctx.lineTo(_path[2], _path[3]);
				ctx.lineTo(_path[4], _path[5]);
				ctx.closePath();
				ctx.fill();

				//ctx.fillRect(x*wh/bsize+gap[3], y*wh/bsize+gap[3], wh/bsize-gap[3]*2, wh/bsize-gap[3]*2);
			}
		}
	}else if(winner>0){
		var result="";
		ctx.font = "20px Comic Sans MS";
		ctx.textAlign = "center";
		if(winner<3){
			if(winner==player){
				result="You won :D";
			}else{
				result="You lost :<";
			}
		}else if(winner==3){
			result="Its a tie!";
		}
		ctx.fillStyle = "#ffffff60";
		ctx.fillRect(0, wh*0.4, wh, wh*0.2);
		ctx.fillStyle = "#000000";
		if(moves==0){
			result="Objective: Block the other player";
		}else{
			ctx.fillText("Game Over", wh/2, wh*0.49); 
		}
		ctx.fillText(result, wh/2, wh*0.57); 
	}
}
var encoder = new GIFEncoder();
encoder.setRepeat(0); //0  -> loop forever
encoder.setDelay(100); //go to next frame every n 
// encoder.start();
// encoder.addFrame(ctx);
// encoder.finish();
// encoder.download("download.gif");
//encoder.addFrame(ctx);
//encoder.finish();
//encoder.download("download.gif");

const _URL="<?= strtok($_SERVER['REQUEST_URI'], '?')."?game=".$gid ?>";
const _URL2="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>";


const wh=200;
var data; //JSON.parse(Get(URL+"&json=1"));
const canvasElem = document.getElementById("canv"); 
canvasElem.addEventListener("mouseup", function(e){ press(e)} ); 
canvasElem.addEventListener("mousemove", function(e){ move(e)} ); 

const canvas = document.getElementById("canv");
const ctx = canvas.getContext("2d");

const gap=[5,2,11,7];
const clr=["#c0c0c0","#ff0000","#0000ff","#008000","#000000","#ffffff","#000000","#ffffff"];
var bc={step:0, move:0};
var anim_moves=-1;


var toplink1 = document.getElementById("toplink");
var remlink1 = document.getElementById("remlink");
var newgame1 = document.getElementById("remlink");

//var remid="<? echo $remid ?>";

var ref=setInterval(refresh, 2000);
</script>
</body>