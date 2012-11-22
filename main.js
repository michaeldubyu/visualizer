(function($){
  myRenderer = function(canvas){
	var canvas = $(canvas).get(0)
	var ctx = canvas.getContext("2d");
	var gfx = arbor.Graphics(canvas)
	var particleSystem = null

	var that = {
	  init:function(system){
		particleSystem = system
		particleSystem.screenSize(canvas.width, canvas.height) 
		particleSystem.screenPadding(20,60,20,60);
		$(window).resize(that.resize);
		that.resize();
		that.initMouseHandling()
	  },

	  redraw:function(){
		if (!particleSystem) return
		gfx.clear() // convenience ƒ: clears the whole canvas rect

		// draw the nodes & save their bounds for edge drawing
		var nodeBoxes = {}
		particleSystem.eachNode(function(node, pt){
		  // node: {mass:#, p:{x,y}, name:"", data:{}}
		  // pt:   {x:#, y:#}  node position in screen coords

		  // determine the box size and round off the coords if we'll be 
		  // drawing a text label (awful alignment jitter otherwise...)
		  var label = node.data.label||""
		  var w = ctx.measureText(""+label).width + 20
		  if (!(""+label).match(/^[ \t]*$/)){
			pt.x = Math.floor(pt.x)
			pt.y = Math.floor(pt.y)
		  }else{
			label = null
		  }

		  // draw a rectangle centered at pt
		  if (node.data.color) ctx.fillStyle = node.data.color
		  else ctx.fillStyle = "rgba(0,0,0,.2)"
		  if (node.data.color=='none') ctx.fillStyle = "#333"

		  if (node.data.shape=='dot'){
			gfx.oval(pt.x-w/2, pt.y-w/2, w,w, {fill:ctx.fillStyle})
			nodeBoxes[node.name] = [pt.x-w/2, pt.y-w/2, w,w]
		  }else{
			gfx.rect(pt.x-w/2, pt.y-10, w,20, 4, {fill:ctx.fillStyle})
			nodeBoxes[node.name] = [pt.x-w/2, pt.y-11, w, 22]
		  }

		  // draw the text
		  if (label){
			ctx.font = "15px Helvetica"
			ctx.textAlign = "center"
			ctx.fillStyle = "white"
			if (node.data.color=='none') ctx.fillStyle = '#333333'
			ctx.fillText(label||"", pt.x, pt.y+4)
			ctx.fillText(label||"", pt.x, pt.y+4)
		  }
		})    			


		// draw the edges
		particleSystem.eachEdge(function(edge, pt1, pt2){
		  // edge: {source:Node, target:Node, length:#, data:{}}
		  // pt1:  {x:#, y:#}  source position in screen coords
		  // pt2:  {x:#, y:#}  target position in screen coords

		  var weight = edge.data.weight
		  var color = edge.data.color

		  if (!color || (""+color).match(/^[ \t]*$/)) color = null

		  // find the start point
		  var tail = intersect_line_box(pt1, pt2, nodeBoxes[edge.source.name])
		  var head = intersect_line_box(tail, pt2, nodeBoxes[edge.target.name])

		  ctx.save() 
			ctx.beginPath()
			ctx.lineWidth = (!isNaN(weight)) ? parseFloat(weight) : 1
			ctx.strokeStyle = (color) ? color : "#a6a6a6"
			ctx.fillStyle = null

			ctx.moveTo(tail.x, tail.y)
			ctx.lineTo(head.x, head.y)
			ctx.stroke()
		  ctx.restore()

		  // draw an arrowhead if this is a -> style edge
		  if (edge.data.directed){
			ctx.save()
			  // move to the head position of the edge we just drew
			  var wt = !isNaN(weight) ? parseFloat(weight) : 1
			  var arrowLength = 6 + wt
			  var arrowWidth = 2 + wt
			  ctx.fillStyle = (color) ? color : "#a6a6a6"
			  ctx.translate(head.x, head.y);
			  ctx.rotate(Math.atan2(head.y - tail.y, head.x - tail.x));

			  // delete some of the edge that's already there (so the point isn't hidden)
			  ctx.clearRect(-arrowLength/2,-wt/2, arrowLength/2,wt)

			  // draw the chevron
			  ctx.beginPath();
			  ctx.moveTo(-arrowLength, arrowWidth);
			  ctx.lineTo(0, 0);
			  ctx.lineTo(-arrowLength, -arrowWidth);
			  ctx.lineTo(-arrowLength * 0.8, -0);
			  ctx.closePath();
			  ctx.fill();
			ctx.restore()
		  }
		})
	  }, 
	  resize:function(){
     	  	var w = $(window).width();
        	var h = $(window).height()-148; //hack to solve resize problems
		canvas.width = w; canvas.height = h // resize the canvas element to fill the screen
        	particleSystem.screenSize(w,h) // inform the system so it can map coords for us
        	that.redraw();
	  },
	  initMouseHandling:function(){
		// no-nonsense drag and drop (thanks springy.js)
		selected = null;
		nearest = null;
		var dragged = null;
		var oldmass = 1

		// set up a handler object that will initially listen for mousedowns then
		// for moves and mouseups while dragging
		var handler = {
		  clicked:function(e){
			var pos = $(canvas).offset();
			_mouseP = arbor.Point(e.pageX-pos.left, e.pageY-pos.top)
			selected = nearest = dragged = particleSystem.nearest(_mouseP);
			if (dragged.node !== null) dragged.node.fixed = true

			$(canvas).bind('mousemove', handler.dragged)
			$(window).bind('mouseup', handler.dropped)

			return false;
		  }
		}
		$(canvas).mousedown(handler.clicked);
	  }
	}

	
	// helpers for figuring out where to draw arrows (thanks springy.js)
	var intersect_line_line = function(p1, p2, p3, p4)
	{
	  var denom = ((p4.y - p3.y)*(p2.x - p1.x) - (p4.x - p3.x)*(p2.y - p1.y));
	  if (denom === 0) return false // lines are parallel
	  var ua = ((p4.x - p3.x)*(p1.y - p3.y) - (p4.y - p3.y)*(p1.x - p3.x)) / denom;
	  var ub = ((p2.x - p1.x)*(p1.y - p3.y) - (p2.y - p1.y)*(p1.x - p3.x)) / denom;

	  if (ua < 0 || ua > 1 || ub < 0 || ub > 1)  return false
	  return arbor.Point(p1.x + ua * (p2.x - p1.x), p1.y + ua * (p2.y - p1.y));
	}

	var intersect_line_box = function(p1, p2, boxTuple)
	{
	  var p3 = {x:boxTuple[0], y:boxTuple[1]},
	  w = boxTuple[2],
	  h = boxTuple[3]

	  var tl = {x: p3.x, y: p3.y};
	  var tr = {x: p3.x + w, y: p3.y};
	  var bl = {x: p3.x, y: p3.y + h};
	  var br = {x: p3.x + w, y: p3.y + h};

	  return intersect_line_line(p1, p2, tl, tr) ||
		intersect_line_line(p1, p2, tr, br) ||
		intersect_line_line(p1, p2, br, bl) ||
		intersect_line_line(p1, p2, bl, tl) ||
		false
	}
	return that
  }    
	
	function htmlDecode(input){
  		var e = document.createElement('div');
  		e.innerHTML = input;
  		return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
	}	
	
	$(document).ready(function(){			
		var sys = arbor.ParticleSystem(500, 500, 0.7, true, 60, 0.02, 0.8);
		sys.parameters({gravity:true});
		sys.renderer = myRenderer("#viewport");

		$("#form form").submit(function(){
			var val = $("#userid:input").val();
			if (/^[a-z0-9-_]+$/i.test(val)) location.href = "/" + val;
			else $("#user_feedback").text("Please enter a valid steamid or community id!");
			return false;
		});
	
		var user = location.pathname.split("/")[1];
		$("#userid:input").attr("value",user);	
	
		if (user==''){
			d = new Object();
			d.label = "Gabe Newell";
			e = new Object();
			e.label = "Robin Walker";
			f = new Object();
			f.label = "Gordon Freeman";
			data = new Object();
			data.directed = true;

			sys.addNode('a', d);
			sys.addNode('e', e);
			sys.addNode('f', f);
			sys.addEdge('f','a', data);
			sys.addEdge('f','e');
		}	
		else if (/^[a-z0-9-_]+$/i.test(user)){	
                	$("#loading").show();
			$("#loading_msg").show();
			$("#loading_msg").html("Downloading friend information...");
			$.ajax({
                       		url: "json_flist.php?userid=" + user,
                       		method: 'GET',
                       		dataType: 'json',
                		success: addFriends,
                		cache:false
			});

			function addSelf(d){
				self_name = htmlDecode(d.display_name);
				self = new Object();
				self.label = self_name;
				self.color = '#b01800';
				self.mass = 2;	
				self.fixed = true;
				self.x=-0.5;
				self.y=-0.5;	
				sys.addNode(self_name,self);
			}

			function addFriends(data){
				$("#loading").hide();
				$("#loading_msg").hide();
				if (data.display_name!='null'){
					addSelf(data[0]);

					$.each(data.splice(1), function(index,item){
						friend = new Object();
						friend.label = htmlDecode(item.display_name);
						friend.steamid = item.steamid;
						
						sys.addNode(friend.label,friend);
						sys.addEdge(self_name,friend.label);
					});
				}else{
					error = new Object();
					if (data.display_name=='null')error.label = "Invalid SteamID or Community ID given, or privacy is not set to public!";
					else error.label = "Friend data unavailable - please check your privacy settings or refresh.";
					error.color = "#B01800";
					error.fixed = true;
					error.x=-0.5;
					error.y=-0.5;
					sys.addNode(error.label,error);	
				}	

			}
		}
		else{
		        $("#user_feedback").text("Please enter a valid steamid or community id!");
		}
	});

})(this.jQuery)


