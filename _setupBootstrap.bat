mkdir webroot\css\bootstrap 
mkdir webroot\js\bootstrap
mkdir webroot\js\jquery 
mkdir webroot\css\fonts

copy vendor\twbs\bootstrap\dist\css\* webroot\css\bootstrap\.
copy vendor\twbs\bootstrap\dist\js\* webroot\js\bootstrap\.
copy vendor\twbs\bootstrap\dist\fonts\* webroot\css\fonts\.
copy vendor\components\jquery\* webroot\bootstrap_u_i\js\.