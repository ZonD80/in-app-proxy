<pre>
<?php
    /*
    in-app-proxy signing tester
    Copyright ZonD80
    Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
    */

    $private_key='-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAM2mruqEJIG6TNlQ
906ht7+8Nd5Zas6ST0KC7bUQNM52wHQ0vj6XmljvL/cqul6N4bBQsFrwS7SNNZ/s
iChH/teyHOz87ia//NBbIMkKdVi7hoqAY+aSYX4g+kEiXcqVtdSnvb/Yao9+MIyo
GF6iKQ5TOFVT7/huLf71Y7N/ARiRAgMBAAECgYAwBRbc7eQ0YpMlP3Gv67UjUUhm
1gxJlgJp7nahC9q4xyPjPpmZtf61e4yAs3p3L7weVokHgwq6ayq1YB7fAQixW8X1
V2hiAC4gNPqktUdkkmZHqFsY16EJUPUiPdphP4lZcesp3zDYYJ7si+CH8FClRkTT
q1VENzqGGJBUKA7YkQJBAO98LYvJwDJxF2aqxM3HR2VhOs9rr+3oaSNxbbMLY1t9
qj8VqjAHBYX54ywqusCd2yco4meENTmHLGG3oGWNHZUCQQDb1TUOPxfEkIRkNibE
Nu2XNNFg7TgnE4oXeSXS+zphCW0EqJ+Y2kDbb16knH357KseX+G7aCPSl5PGkhUz
ZjgNAkEA00KTFy6JmrXC8/GPHQw/gkJMU+/mSZPtM7P7FqfkJTBs/6uH70gyaiav
bSXgisx2KExbtP+eyDnjP+xx1UOwJQJBAIgxTM9oszbqObtD+TxyszuMU3NzQ+ih
qFnmilJtprtbdZj/RvERtkC8fKwK79kYkOMej+DlIdxkX/8TneLcHzkCQQCWoZUC
kWXIN8JeWkfCDLOtf1+XdZ53n4jn+ciMzX7zu3FATHgr36tsNs7Q5bncoKLiefuN
U5tRKPm+9w0Bm+Pi
-----END PRIVATE KEY-----
';

    function NS_to_array($data) {
        preg_match_all('#"(.*?)" \= "(.*?)"\;#si',$data,$matches);
        foreach ($matches[1] as $key=>$match) {
            $return[$match] = $matches[2][$key];
        }
        return $return;
    }

    function array_to_NS($ar) {
        $return = array();
        foreach ($ar as $k=>$v) {
            $return[] = "\t\"$k\" = \"$v\"";
        }
        return "{\n".implode(";\n",$return).";\n}";
    }

  $receipt = 'ewoJInNpZ25hdHVyZSIgPSAiQXBkeEpkdE53UFUyckE1L2NuM2tJTzFPVGsyNWZlREthMGFhZ3l5UnZlV2xjRmxnbHY2UkY2em5raUJTM3VtOVVjN3BWb2IrUHFaUjJUOHd5VnJITnBsb2YzRFgzSXFET2xXcSs5MGE3WWwrcXJSN0E3ald3dml3NzA4UFMrNjdQeUhSbmhPL0c3YlZxZ1JwRXI2RXVGeWJpVTFGWEFpWEpjNmxzMVlBc3NReEFBQURWekNDQTFNd2dnSTdvQU1DQVFJQ0NHVVVrVTNaV0FTMU1BMEdDU3FHU0liM0RRRUJCUVVBTUg4eEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURXpNREVHQTFVRUF3d3FRWEJ3YkdVZ2FWUjFibVZ6SUZOMGIzSmxJRU5sY25ScFptbGpZWFJwYjI0Z1FYVjBhRzl5YVhSNU1CNFhEVEE1TURZeE5USXlNRFUxTmxvWERURTBNRFl4TkRJeU1EVTFObG93WkRFak1DRUdBMVVFQXd3YVVIVnlZMmhoYzJWU1pXTmxhWEIwUTJWeWRHbG1hV05oZEdVeEd6QVpCZ05WQkFzTUVrRndjR3hsSUdsVWRXNWxjeUJUZEc5eVpURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd2daOHdEUVlKS29aSWh2Y05BUUVCQlFBRGdZMEFNSUdKQW9HQkFNclJqRjJjdDRJclNkaVRDaGFJMGc4cHd2L2NtSHM4cC9Sd1YvcnQvOTFYS1ZoTmw0WElCaW1LalFRTmZnSHNEczZ5anUrK0RyS0pFN3VLc3BoTWRkS1lmRkU1ckdYc0FkQkVqQndSSXhleFRldngzSExFRkdBdDFtb0t4NTA5ZGh4dGlJZERnSnYyWWFWczQ5QjB1SnZOZHk2U01xTk5MSHNETHpEUzlvWkhBZ01CQUFHamNqQndNQXdHQTFVZEV3RUIvd1FDTUFBd0h3WURWUjBqQkJnd0ZvQVVOaDNvNHAyQzBnRVl0VEpyRHRkREM1RllRem93RGdZRFZSMFBBUUgvQkFRREFnZUFNQjBHQTFVZERnUVdCQlNwZzRQeUdVakZQaEpYQ0JUTXphTittVjhrOVRBUUJnb3Foa2lHOTJOa0JnVUJCQUlGQURBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQUVhU2JQanRtTjRDL0lCM1FFcEszMlJ4YWNDRFhkVlhBZVZSZVM1RmFaeGMrdDg4cFFQOTNCaUF4dmRXLzNlVFNNR1k1RmJlQVlMM2V0cVA1Z204d3JGb2pYMGlreVZSU3RRKy9BUTBLRWp0cUIwN2tMczlRVWU4Y3pSOFVHZmRNMUV1bVYvVWd2RGQ0TndOWXhMUU1nNFdUUWZna1FRVnk4R1had1ZIZ2JFL1VDNlk3MDUzcEdYQms1MU5QTTN3b3hoZDNnU1JMdlhqK2xvSHNTdGNURXFlOXBCRHBtRzUrc2s0dHcrR0szR01lRU41LytlMVFUOW5wL0tsMW5qK2FCdzdDMHhzeTBiRm5hQWQxY1NTNnhkb3J5L0NVdk02Z3RLc21uT09kcVRlc2JwMGJzOHNuNldxczBDOWRnY3hSSHVPTVoydG04bnBMVW03YXJnT1N6UT09IjsKCSJwdXJjaGFzZS1pbmZvIiA9ICJld29KSW05eWFXZHBibUZzTFhCMWNtTm9ZWE5sTFdSaGRHVXRjSE4wSWlBOUlDSXlNREV5TFRBM0xURXlJREExT2pVME9qTTFJRUZ0WlhKcFkyRXZURzl6WDBGdVoyVnNaWE1pT3dvSkluQjFjbU5vWVhObExXUmhkR1V0YlhNaUlEMGdJakV6TkRJd09UYzJOelU0T0RJaU93b0pJbTl5YVdkcGJtRnNMWFJ5WVc1ellXTjBhVzl1TFdsa0lpQTlJQ0l4TnpBd01EQXdNamswTkRrME1qQWlPd29KSW1KMmNuTWlJRDBnSWpFdU5DSTdDZ2tpWVhCd0xXbDBaVzB0YVdRaUlEMGdJalExTURVME1qSXpNeUk3Q2draWRISmhibk5oWTNScGIyNHRhV1FpSUQwZ0lqRTNNREF3TURBeU9UUTBPVFF5TUNJN0Nna2ljWFZoYm5ScGRIa2lJRDBnSWpFaU93b0pJbTl5YVdkcGJtRnNMWEIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFek5ESXdPVGMyTnpVNE9ESWlPd29KSW1sMFpXMHRhV1FpSUQwZ0lqVXpOREU0TlRBME1pSTdDZ2tpZG1WeWMybHZiaTFsZUhSbGNtNWhiQzFwWkdWdWRHbG1hV1Z5SWlBOUlDSTVNRFV4TWpNMklqc0tDU0p3Y205a2RXTjBMV2xrSWlBOUlDSmpiMjB1ZW1Wd2RHOXNZV0l1WTNSeVltOXVkWE11YzNWd1pYSndiM2RsY2pFaU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVaUlEMGdJakl3TVRJdE1EY3RNVElnTVRJNk5UUTZNelVnUlhSakwwZE5WQ0k3Q2draWIzSnBaMmx1WVd3dGNIVnlZMmhoYzJVdFpHRjBaU0lnUFNBaU1qQXhNaTB3TnkweE1pQXhNam8xTkRvek5TQkZkR012UjAxVUlqc0tDU0ppYVdRaUlEMGdJbU52YlM1NlpYQjBiMnhoWWk1amRISmxlSEJsY21sdFpXNTBjeUk3Q2draWNIVnlZMmhoYzJVdFpHRjBaUzF3YzNRaUlEMGdJakl3TVRJdE1EY3RNVElnTURVNk5UUTZNelVnUVcxbGNtbGpZUzlNYjNOZlFXNW5aV3hsY3lJN0NuMD0iOwoJInBvZCIgPSAiMTciOwoJInNpZ25pbmctc3RhdHVzIiA9ICIwIjsKfQ==';

//$receipt = 'ewoJInNpZ25hdHVyZSIgPSAiQXNNNVl6TlRFWXRPYjN0V0NvUTdiTW81dmVnYVB0QTRrSFVzb2oybm5TOE9vQzd1OFd4L2JkbE53V0pLNS93T2liYWE2STZaSk93bm9MOHRSbnpWdGVKanhFNEIvbloxd0xyQm96ZytyRWVXR1lYeXRLNjFZS2xpdFV4am1VUDhuRUh3T0hML1paRkYwT1RDZ0MrNnUvR3gxMTRNNVc4VktkZ2t6L0xvVHRtSkFBQURGakNDQXhJd2dnSDZBZ2hsRkpGTjJWZ0V0VEFOQmdrcWhraUc5dzBCQVFVRkFEQ0JqREVMTUFrR0ExVUVCaE1DVlZNeEV6QVJCZ05WQkFnTUNrTmhiR2xtYjNKdWFXRXhFekFSQmdOVkJBY01Da052ZFhCbGNuUnBibTh4RXpBUkJnTlZCQW9NQ2tGd2NHeGxJRWx1WXk0eEpqQWtCZ05WQkFzTUhVRndjR3hsSUVObGNuUnBabWxqWVhScGIyNGdRWFYwYUc5eWFYUjVNUll3RkFZRFZRUUREQTFCY0hCc1pTQlNiMjkwSUVOQk1CNFhEVEV5TURjeU16RTVNemt3TWxvWERURXpNRGN5TXpFNU16a3dNbG93Z1kweEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUlEQXBEWVd4cFptOXlibWxoTVJJd0VBWURWUVFIREFsRGRYQmxjblJwYm04eEV6QVJCZ05WQkFvTUNrRndjR3hsSUVsdVl5NHhHekFaQmdOVkJBc01Fa0Z3Y0d4bElHbFVkVzVsY3lCVGRHOXlaVEVqTUNFR0ExVUVBd3dhVUhWeVkyaGhjMlZTWldObGFYQjBRMlZ5ZEdsbWFXTmhkR1V3Z1o4d0RRWUpLb1pJaHZjTkFRRUJCUUFEZ1kwQU1JR0pBb0dCQU0ybXJ1cUVKSUc2VE5sUTkwNmh0Nys4TmQ1WmFzNlNUMEtDN2JVUU5NNTJ3SFEwdmo2WG1sanZML2NxdWw2TjRiQlFzRnJ3UzdTTk5aL3NpQ2hIL3RleUhPejg3aWEvL05CYklNa0tkVmk3aG9xQVkrYVNZWDRnK2tFaVhjcVZ0ZFNudmIvWWFvOStNSXlvR0Y2aUtRNVRPRlZUNy9odUxmNzFZN04vQVJpUkFnTUJBQUV3RFFZSktvWklodmNOQVFFRkJRQURnZ0VCQU1IM1pNYlRSanhuMllaZWNzdExqT3UvOWNGeWp3TFFSSUlSYk1wVlBEaDlNZjRTNy9hS3RTTWI3bS9mNWZJK0ZlNTA1eEwzaG1YSk1EdVFpay8xV0FGd3FleUdYbkEwNjQzVXdoZ2orUzBKdVZsbHA4dFJmOVp3TVN5RGVFOHNYWmVKaThOM3JIQnNHbFJORjFkUkpjQmRlUmxvZ2dGZ3NIREhPb3FaUDBlanUvb3lCR3Bqa0JhKzR2WWdVemc3N0pzeXJSUVk5NG9ITkZwWk44WU1sdTVHK3NLR1AwVXk3a0hyZENwMHRVWWFkMmE3OVR0NGZDVUpOMnNmWGV1SXE3aDlmWHlCSVpxZUZvRk9DOFczTk4zNkVlMi91cTk4OHFBMlRyVmdDWVNSdkk3TnNtN3VMd1lxOGVsUFc0cnVZcmVzSnU5anp2Rk1RRmhIWmRLaGd5MD0iOwoJInB1cmNoYXNlLWluZm8iID0gImV3b0pJbTl5YVdkcGJtRnNMWEIxY21Ob1lYTmxMV1JoZEdVdGNITjBJaUE5SUNJeU1ERXlMVEEzTFRJMElEQTJPalU1T2pJd0lFRnRaWEpwWTJFdlRHOXpYMEZ1WjJWc1pYTWlPd29KSW5CMWNtTm9ZWE5sTFdSaGRHVXRiWE1pSUQwZ0lqRXpORE14TXpnek5qQXdNREFpT3dvSkltOXlhV2RwYm1Gc0xYUnlZVzV6WVdOMGFXOXVMV2xrSWlBOUlDSXlOVEV5TXpBaU93b0pJbUoyY25NaUlEMGdJakV1TkM0eElqc0tDU0poY0hBdGFYUmxiUzFwWkNJZ1BTQWlORFV3TlRReU1qTXpJanNLQ1NKMGNtRnVjMkZqZEdsdmJpMXBaQ0lnUFNBaU1qVXhNak13SWpzS0NTSnhkV0Z1ZEdsMGVTSWdQU0FpTVNJN0Nna2liM0pwWjJsdVlXd3RjSFZ5WTJoaGMyVXRaR0YwWlMxdGN5SWdQU0FpTVRNME16RXpPRE0yTURBd01DSTdDZ2tpYVhSbGJTMXBaQ0lnUFNBaU1TSTdDZ2tpZG1WeWMybHZiaTFsZUhSbGNtNWhiQzFwWkdWdWRHbG1hV1Z5SWlBOUlDSTVORGc1TURreklqc0tDU0p3Y205a2RXTjBMV2xrSWlBOUlDSmpiMjB1ZW1Wd2RHOXNZV0l1WTNSeVltOXVkWE11YzNWd1pYSndiM2RsY2pRaU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVaUlEMGdJakl3TVRJdE1EY3RNalFnTVRNNk5UazZNakFnUlhSakwwZE5WQ0k3Q2draWIzSnBaMmx1WVd3dGNIVnlZMmhoYzJVdFpHRjBaU0lnUFNBaU1qQXhNaTB3TnkweU5DQXhNem8xT1RveU1DQkZkR012UjAxVUlqc0tDU0ppYVdRaUlEMGdJbU52YlM1NlpYQjBiMnhoWWk1amRISmxlSEJsY21sdFpXNTBjeUk3Q2draWNIVnlZMmhoYzJVdFpHRjBaUzF3YzNRaUlEMGdJakl3TVRJdE1EY3RNalFnTURZNk5UazZNakFnUVcxbGNtbGpZUzlNYjNOZlFXNW5aV3hsY3lJN0NuMD0iOwoJInBvZCIgPSAiMTciOwoJInNpZ25pbmctc3RhdHVzIiA9ICIwIjsKfQ==';
    $NSreceipt = base64_decode($receipt);

$receiptarray = NS_to_array($NSreceipt);


var_dump($receiptarray);

$signature = base64_decode($receiptarray['signature']);

//var_dump($signature);
print "==================================================\n\n";

    $ar['receipt_version'] = substr($signature,0,1);

var_dump(chr(2));

$ar['sign'] = substr($signature,1,128);

$ar['certlen'] = unpack('N',substr($signature,129,4));

$ar['cert'] = substr($signature,133,$ar['certlen'][1]);

print '<h1>fuck</h1>';
file_put_contents('purchasecert.cer',$ar['cert']);

var_dump($ar);

    print "==================================================\n\n";

$certdata = chunk_split(base64_encode($ar['cert']),64,"\n");
    ////var_dump($certdata);

   $cert = "-----BEGIN CERTIFICATE-----\n".$certdata."-----END CERTIFICATE-----\n";

    var_dump($cert);
    $pubkeyid = openssl_get_publickey($cert);


    var_dump($pubkeyid);
// state whether signature is okay or not*/

    //file_put_contents('data.txt',$receiptarray['purchase-info']);

    var_dump(openssl_verify($ar['receipt_version'].base64_decode($receiptarray['purchase-info']), $ar['sign'], $pubkeyid));
    $pkeyid = openssl_get_privatekey($private_key);
// compute signature
    openssl_sign(chr(2).base64_decode($receiptarray['purchase-info']), $signature, $pkeyid);

var_dump($signature);



$pucert = file_get_contents('pucert.cer');

$signature = chr(2).$signature.pack('N',strlen($pucert)).$pucert;
// free the key from memory
    openssl_free_key($pkeyid);


    print "==================================================\n\n";

    $certdata = chunk_split(base64_encode($ar['cert']),64,"\n");
    ////var_dump($certdata);

    $cert = "-----BEGIN CERTIFICATE-----\n".$certdata."-----END CERTIFICATE-----\n";

    var_dump($cert);
    $pubkeyid = openssl_get_publickey($cert);


    var_dump($pubkeyid);
// state whether signature is okay or not*/

    //file_put_contents('data.txt',$receiptarray['purchase-info']);

    var_dump(openssl_verify($ar['receipt_version'].base64_decode($receiptarray['purchase-info']), $ar['sign'], $pubkeyid));

    ?>
</pre>