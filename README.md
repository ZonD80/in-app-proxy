# in-app-proxy

This code allows you to emulate AppStore in-app purchasing algorithms.

## Setup environment
You need UNIX, DNSMasq running with this configuration:
`
server=/ax.init.itunes.apple.com/8.8.8.8
server=/itunes.apple.com/8.8.8.8
server=/ets.gameloft.com/8.8.8.8
server=/vgold.gameloft.com/8.8.8.8
server=/itunes.com/8.8.8.8
address=/.itunes.apple.com/91.224.160.136
address=/edgesuite.net/91.224.160.136
address=/api.textnow.me/127.0.0.1
address=/warspark.com/127.0.0.1
address=/gameloft.com/91.224.160.136
address=/receipts.jamiesrecipes.zolmo.com/127.0.0.1
address=/popcap.com/127.0.0.1
address=/digitalchocolate.com/127.0.0.1
address=/beeblex.com/127.0.0.1
address=/highnoon.happylatte.com/127.0.0.1
address=/dc.full-fat.com/91.224.160.136
address=/mobile.ext.terrhq.ru/91.224.160.136
address=/api.tapsonic.co.kr/127.0.0.1
address=/bubble.teamlava.com/127.0.0.1
address=/csrrun.naturalmotion.com/127.0.0.1
address=/testflightapp.com/127.0.0.1
`
Also, you need nginx(apache) with php as module or cgi with these extensions:
php-curl, pecl_http, php-xml

Note, pecl_http need to be built from sources, so install php-pear, php-dev and gcc.

Next, virtualhost with certificate and key itcert.pem, itcert.key (or generate yours) listens on *.itunes.apple.com on 443,
virtualhost listens on *.itunes.apple.com on 80,
pucert.cer - purchase receipt certificate with keylength = 1024,
virtualhost listens on * on 443 for devs server emulation,
virtualhost listens on * on 80 for devs server emulation,

rewrites all on iapcracker.php on *.itunes.apple.com,
rewrites all to index.php on *.

Here is example for nginx:

`
if (!-e $request_filename) {
rewrite ^/(.*)$ /iapcracker.php?URL=$1 last;
break;
}
`

## Feel free to connect

Install two certificates (cacert.pem, itcert.pem) on your iDevice, chage DNS to IP of your server. U're done!