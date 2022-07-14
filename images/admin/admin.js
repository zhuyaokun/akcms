function clicklink(obj)
{
var node;
node = document.getElementById("favorite").childNodes;
for(var i = 0; i < node.length; i ++)
{
	node[i].style.backgroundColor = '#2569B3';
}
node = document.getElementById("createhtml").childNodes;
for(var i = 0; i < node.length; i ++)
{
	node[i].style.backgroundColor = '#2569B3';
}
node = document.getElementById("webeditor").childNodes;
for(var i = 0; i < node.length; i ++)
{
	node[i].style.backgroundColor = '#2569B3';
}
node = document.getElementById("account").childNodes;
for(var i = 0; i < node.length; i ++)
{
	node[i].style.backgroundColor = '#2569B3';
}
obj.style.backgroundColor = '#328bec';
}