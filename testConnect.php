<?php
/*
RavencoinRPC-PHP
A basic PHP script for testing connection to Ravencoin's Network.
https://github.com/HazeDevelopment/ravencoinrpc-php/

====================
RavencoinRPC-PHP Connection Test

A basic example of how to disply node info and confirm that connection 
has successfully been established to the RPC host on the server.
====================
*/
//Include RavencoinRPC-PHP class
require_once('ravencoin-rpc.php');

//Initialize RavenCoin connection/object with default host/port
//$ravencoin = new Ravencoin('user','pass');
//Or specify a host and port.
$ravencoin = new Ravencoin('user','pass','localhost','8776');

//Get info on the ravend daemon
$ravencoin->getinfo();

//Transaction information
$ravencoin->getrawtransaction('2b849538e4d43a20daf8b19a3bac762c7edad16386e3cd7205a18035aa6646b0',1);

//Block Information
$ravencoin->getblock('000000000001f38aa42b905231c7a8a12e4508de126b683f8165f2589e844070');

//Check HTTP status with $ravencoin->status
if ($ravencoin->status == 500) {
	echo "Connection to RPC Server Established!";
} else {
	echo "HTTP Error: ".$ravencoin->status;
}

