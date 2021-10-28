{composerEnv, fetchurl, fetchgit ? null, fetchhg ? null, fetchsvn ? null, noDev ? false}:

let
  packages = {
    "composer/package-versions-deprecated" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "composer-package-versions-deprecated-b174585d1fe49ceed21928a945138948cb394600";
        src = fetchurl {
          url = "https://api.github.com/repos/composer/package-versions-deprecated/zipball/b174585d1fe49ceed21928a945138948cb394600";
          sha256 = "0m5hd3wfaka53n51b9aavyifwc2bdyr3jwywpkmpyrlmmn67c8ax";
        };
      };
    };
    "composer/xdebug-handler" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "composer-xdebug-handler-f27e06cd9675801df441b3656569b328e04aa37c";
        src = fetchurl {
          url = "https://api.github.com/repos/composer/xdebug-handler/zipball/f27e06cd9675801df441b3656569b328e04aa37c";
          sha256 = "0db49yf7zcf4q57ba48n10cyrdjf7s598321m69dkb4dph0yc5qh";
        };
      };
    };
    "doctrine/annotations" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-annotations-5b668aef16090008790395c02c893b1ba13f7e08";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/annotations/zipball/5b668aef16090008790395c02c893b1ba13f7e08";
          sha256 = "129dixpipqfi55yq1rcp7dwj1yl1w70i462rs16ma4bn5vzxqz5s";
        };
      };
    };
    "doctrine/cache" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-cache-4cf401d14df219fa6f38b671f5493449151c9ad8";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/cache/zipball/4cf401d14df219fa6f38b671f5493449151c9ad8";
          sha256 = "1hklk08cld4i5113f0a87778xmqnivkrck718wjbp1z6k76sbnsh";
        };
      };
    };
    "doctrine/collections" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-collections-1958a744696c6bb3bb0d28db2611dc11610e78af";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/collections/zipball/1958a744696c6bb3bb0d28db2611dc11610e78af";
          sha256 = "0ygsw2vgrkz1wd9aw6gd8y6kjwxq9bjqcp3dgdx0p8w9mz7bdpm5";
        };
      };
    };
    "doctrine/common" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-common-6d970a11479275300b5144e9373ce5feacfa9b91";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/common/zipball/6d970a11479275300b5144e9373ce5feacfa9b91";
          sha256 = "1b9ms270iqr0kqnbbjzfl8s3ddmiyll0nqr7qc8x2qqa4lqh76mb";
        };
      };
    };
    "doctrine/dbal" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-dbal-2411a55a2a628e6d8dd598388ab13474802c7b6e";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/dbal/zipball/2411a55a2a628e6d8dd598388ab13474802c7b6e";
          sha256 = "19vyv64ikbzk0pm9nn67a2kidhfvfcm9s5d91h0hk6kbq85f292v";
        };
      };
    };
    "doctrine/deprecations" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-deprecations-9504165960a1f83cc1480e2be1dd0a0478561314";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/deprecations/zipball/9504165960a1f83cc1480e2be1dd0a0478561314";
          sha256 = "04kpbzk5iw86imspkg7dgs54xx877k9b5q0dfg2h119mlfkvxil6";
        };
      };
    };
    "doctrine/event-manager" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-event-manager-41370af6a30faa9dc0368c4a6814d596e81aba7f";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/event-manager/zipball/41370af6a30faa9dc0368c4a6814d596e81aba7f";
          sha256 = "0pn2aiwl4fvv6fcwar9alng2yrqy8bzc58n4bkp6y2jnpw5gp4m8";
        };
      };
    };
    "doctrine/inflector" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-inflector-8b7ff3e4b7de6b2c84da85637b59fd2880ecaa89";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/inflector/zipball/8b7ff3e4b7de6b2c84da85637b59fd2880ecaa89";
          sha256 = "1l83jbj4k59m1agi041gzx1rxix1wzxw9mvnivmg1hqr158149n7";
        };
      };
    };
    "doctrine/instantiator" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-instantiator-d56bf6102915de5702778fe20f2de3b2fe570b5b";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/instantiator/zipball/d56bf6102915de5702778fe20f2de3b2fe570b5b";
          sha256 = "04rihgfjv8alvvb92bnb5qpz8fvqvjwfrawcjw34pfnfx4jflcwh";
        };
      };
    };
    "doctrine/lexer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-lexer-e864bbf5904cb8f5bb334f99209b48018522f042";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/lexer/zipball/e864bbf5904cb8f5bb334f99209b48018522f042";
          sha256 = "11lg9fcy0crb8inklajhx3kyffdbx7xzdj8kwl21xsgq9nm9iwvv";
        };
      };
    };
    "doctrine/orm" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-orm-81d472f6f96b8b571cafefe8d2fef89ed9446a62";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/orm/zipball/81d472f6f96b8b571cafefe8d2fef89ed9446a62";
          sha256 = "14nm34bzdfal9rqzd55jsi25pr78an4rkgxrz2c4wb1pl2s7qdng";
        };
      };
    };
    "doctrine/persistence" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "doctrine-persistence-5e7bdbbfe9811c06e1f745d1c166647d5c47d6ee";
        src = fetchurl {
          url = "https://api.github.com/repos/doctrine/persistence/zipball/5e7bdbbfe9811c06e1f745d1c166647d5c47d6ee";
          sha256 = "1bmck8ydn7qbapmgl7nfkghkckg04dina6bmldgkhm5lkifkrjd0";
        };
      };
    };
    "evenement/evenement" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "evenement-evenement-531bfb9d15f8aa57454f5f0285b18bec903b8fb7";
        src = fetchurl {
          url = "https://api.github.com/repos/igorw/evenement/zipball/531bfb9d15f8aa57454f5f0285b18bec903b8fb7";
          sha256 = "02mi1lrga41caa25whr6sj9hmmlfjp10l0d0fq8kc3d4483pm9rr";
        };
      };
    };
    "jetbrains/phpstorm-stubs" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "jetbrains-phpstorm-stubs-5348171d79b3761a5e2a3a74a323e73cf2fe6eba";
        src = fetchurl {
          url = "https://api.github.com/repos/JetBrains/phpstorm-stubs/zipball/5348171d79b3761a5e2a3a74a323e73cf2fe6eba";
          sha256 = "1cap4hlismj6lminpbizn5v8rmzhzdyzjwzpgvg38k41d5lxljqq";
        };
      };
    };
    "league/html-to-markdown" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "league-html-to-markdown-0868ae7a552e809e5cd8f93ba022071640408e88";
        src = fetchurl {
          url = "https://api.github.com/repos/thephpleague/html-to-markdown/zipball/0868ae7a552e809e5cd8f93ba022071640408e88";
          sha256 = "1a704if1v2vdn7cpiy563268m36k0hn2d6bkm660bgygbb8xvjaf";
        };
      };
    };
    "nikic/php-parser" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "nikic-php-parser-50953a2691a922aa1769461637869a0a2faa3f53";
        src = fetchurl {
          url = "https://api.github.com/repos/nikic/PHP-Parser/zipball/50953a2691a922aa1769461637869a0a2faa3f53";
          sha256 = "1mkl7lbvyxs7z8lh4p3i0j296hvzslrvwbf9cjhb2qhncsxxqrz6";
        };
      };
    };
    "php-ds/php-ds" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "php-ds-php-ds-b98396862fb8a13cbdbbaf4d18be28ee5c01ed3c";
        src = fetchurl {
          url = "https://api.github.com/repos/php-ds/polyfill/zipball/b98396862fb8a13cbdbbaf4d18be28ee5c01ed3c";
          sha256 = "1lmf6qnw52s2avc8j1ahh8cmjp6zddxvq357220zy69fl6z977sz";
        };
      };
    };
    "phpstan/phpdoc-parser" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpstan-phpdoc-parser-98a088b17966bdf6ee25c8a4b634df313d8aa531";
        src = fetchurl {
          url = "https://api.github.com/repos/phpstan/phpdoc-parser/zipball/98a088b17966bdf6ee25c8a4b634df313d8aa531";
          sha256 = "0qk526jr6j0b84wsik0sar5vsvfy3qgg2kw1m2cmizw88x11axgm";
        };
      };
    };
    "psr/cache" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "psr-cache-aa5030cfa5405eccfdcb1083ce040c2cb8d253bf";
        src = fetchurl {
          url = "https://api.github.com/repos/php-fig/cache/zipball/aa5030cfa5405eccfdcb1083ce040c2cb8d253bf";
          sha256 = "07rnyjwb445sfj30v5ny3gfsgc1m7j7cyvwjgs2cm9slns1k1ml8";
        };
      };
    };
    "psr/container" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "psr-container-8622567409010282b7aeebe4bb841fe98b58dcaf";
        src = fetchurl {
          url = "https://api.github.com/repos/php-fig/container/zipball/8622567409010282b7aeebe4bb841fe98b58dcaf";
          sha256 = "0qfvyfp3mli776kb9zda5cpc8cazj3prk0bg0gm254kwxyfkfrwn";
        };
      };
    };
    "psr/log" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "psr-log-d49695b909c3b7628b6289db5479a1c204601f11";
        src = fetchurl {
          url = "https://api.github.com/repos/php-fig/log/zipball/d49695b909c3b7628b6289db5479a1c204601f11";
          sha256 = "0sb0mq30dvmzdgsnqvw3xh4fb4bqjncx72kf8n622f94dd48amln";
        };
      };
    };
    "react/cache" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-cache-4bf736a2cccec7298bdf745db77585966fc2ca7e";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/cache/zipball/4bf736a2cccec7298bdf745db77585966fc2ca7e";
          sha256 = "07l1gc5lvxspc2gwkwhz0f2al4y452f0n4fdc2g68whxmwm6a6j0";
        };
      };
    };
    "react/dns" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-dns-2a5a74ab751e53863b45fb87e1d3913884f88248";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/dns/zipball/2a5a74ab751e53863b45fb87e1d3913884f88248";
          sha256 = "0p00syq8lx6qivdkvppic7lniavz6vw7cjxf5ad52lzj4d43ryzs";
        };
      };
    };
    "react/event-loop" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-event-loop-be6dee480fc4692cec0504e65eb486e3be1aa6f2";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/event-loop/zipball/be6dee480fc4692cec0504e65eb486e3be1aa6f2";
          sha256 = "1g9ark4cvnkajy3390fr79xvvg1fvhzchrc00cwkf1x7hrcfcms3";
        };
      };
    };
    "react/promise" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-promise-f3cff96a19736714524ca0dd1d4130de73dbbbc4";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/promise/zipball/f3cff96a19736714524ca0dd1d4130de73dbbbc4";
          sha256 = "0wg9260q99z7sapsm43nhh1gl588z238aixjkp081x1h0c8j500m";
        };
      };
    };
    "react/promise-timer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-promise-timer-607dd79990e32fcb402cb0a176b4a4be12f97e7c";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/promise-timer/zipball/607dd79990e32fcb402cb0a176b4a4be12f97e7c";
          sha256 = "032l04rsjrhk7q7bnv4vjxfpp2szzhf50m38p05jyry3bjw5mh72";
        };
      };
    };
    "react/socket" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-socket-aa6e3f8ebcd6dec3ad1ee92a449b4cc341994001";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/socket/zipball/aa6e3f8ebcd6dec3ad1ee92a449b4cc341994001";
          sha256 = "0gdv1xy0rwbs3r5sxgg5lqyn8bjnpj5bwwvgljwwd2fjjflfqkqb";
        };
      };
    };
    "react/stream" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "react-stream-7a423506ee1903e89f1e08ec5f0ed430ff784ae9";
        src = fetchurl {
          url = "https://api.github.com/repos/reactphp/stream/zipball/7a423506ee1903e89f1e08ec5f0ed430ff784ae9";
          sha256 = "1vcn792785hg0991vz3fhdmwl5y47z4g7hvly04y03zmbc0qx0mf";
        };
      };
    };
    "serenata/common" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "serenata-common-c82bc481d92704ce9a417eef29fbaceded79e841";
        src = fetchurl {
          url = "https://gitlab.com/api/v4/projects/Serenata%2Fcommon/repository/archive.zip?sha=c82bc481d92704ce9a417eef29fbaceded79e841";
          sha256 = "14yzskl8fdr1p6w2qgyk7d2zbxn04f26qd140pa28p84c8xmj24p";
        };
      };
    };
    "serenata/name-qualification-utilities" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "serenata-name-qualification-utilities-542a1a0bc3f41b4713830b70a43e659b8174b134";
        src = fetchurl {
          url = "https://gitlab.com/api/v4/projects/Serenata%2Fname-qualification-utilities/repository/archive.zip?sha=542a1a0bc3f41b4713830b70a43e659b8174b134";
          sha256 = "0if5x0l36k66gg1c6anrcljiai2wk2d2b9cs2s4qanx36x77gg67";
        };
      };
    };
    "symfony/config" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-config-4268f3059c904c61636275182707f81645517a37";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/config/zipball/4268f3059c904c61636275182707f81645517a37";
          sha256 = "1izirgswwdmg6kp8akrijgc98221w97rwibrhiz89xlxx3990qyn";
        };
      };
    };
    "symfony/console" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-console-8b1008344647462ae6ec57559da166c2bfa5e16a";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/console/zipball/8b1008344647462ae6ec57559da166c2bfa5e16a";
          sha256 = "1gia4h03rs751qyik3g1k8r9g4n6xc6z60f4f9lh1j11bwyx4mcd";
        };
      };
    };
    "symfony/dependency-injection" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-dependency-injection-e39c344e06a3ceab531ebeb6c077e6652c4a0829";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/dependency-injection/zipball/e39c344e06a3ceab531ebeb6c077e6652c4a0829";
          sha256 = "0j6gcs4pyicw07sqbsx0f8jl4r9pg4llsxybla300phdd74cfl2a";
        };
      };
    };
    "symfony/deprecation-contracts" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-deprecation-contracts-5f38c8804a9e97d23e0c8d63341088cd8a22d627";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/deprecation-contracts/zipball/5f38c8804a9e97d23e0c8d63341088cd8a22d627";
          sha256 = "11k6a8v9b6p0j788fgykq6s55baba29lg37fwvmn4igxxkfwmbp3";
        };
      };
    };
    "symfony/filesystem" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-filesystem-343f4fe324383ca46792cae728a3b6e2f708fb32";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/filesystem/zipball/343f4fe324383ca46792cae728a3b6e2f708fb32";
          sha256 = "0a68w982cy4lqs1hz3y44n4dzsjbl1478x0dg5xmkbbszyiiwapp";
        };
      };
    };
    "symfony/finder" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-finder-a10000ada1e600d109a6c7632e9ac42e8bf2fb93";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/finder/zipball/a10000ada1e600d109a6c7632e9ac42e8bf2fb93";
          sha256 = "0n1i1s9azz27kvys5q8syv6nv4anjj4ash8r5xxrzhqdvrcf8qhn";
        };
      };
    };
    "symfony/polyfill-ctype" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-ctype-46cd95797e9df938fdd2b03693b5fca5e64b01ce";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-ctype/zipball/46cd95797e9df938fdd2b03693b5fca5e64b01ce";
          sha256 = "0z4iiznxxs4r72xs4irqqb6c0wnwpwf0hklwn2imls67haq330zn";
        };
      };
    };
    "symfony/polyfill-intl-grapheme" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-intl-grapheme-16880ba9c5ebe3642d1995ab866db29270b36535";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-intl-grapheme/zipball/16880ba9c5ebe3642d1995ab866db29270b36535";
          sha256 = "0pb57756kvdxksqy2nndf8q7c91p2dzhysa52x2rbhba869760fv";
        };
      };
    };
    "symfony/polyfill-intl-normalizer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-intl-normalizer-8590a5f561694770bdcd3f9b5c69dde6945028e8";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-intl-normalizer/zipball/8590a5f561694770bdcd3f9b5c69dde6945028e8";
          sha256 = "1c60xin00q0d2gbyaiglxppn5hqwki616v5chzwyhlhf6aplwsh3";
        };
      };
    };
    "symfony/polyfill-mbstring" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-mbstring-9174a3d80210dca8daa7f31fec659150bbeabfc6";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-mbstring/zipball/9174a3d80210dca8daa7f31fec659150bbeabfc6";
          sha256 = "17bhba3093di6xgi8f0cnf3cdd7fnbyp9l76d9y33cym6213ayx1";
        };
      };
    };
    "symfony/polyfill-php72" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-php72-9a142215a36a3888e30d0a9eeea9766764e96976";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-php72/zipball/9a142215a36a3888e30d0a9eeea9766764e96976";
          sha256 = "06ipbcvrxjzgvraf2z9fwgy0bzvzjvs5z1j67grg1gb15x3d428b";
        };
      };
    };
    "symfony/polyfill-php73" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-php73-fba8933c384d6476ab14fb7b8526e5287ca7e010";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-php73/zipball/fba8933c384d6476ab14fb7b8526e5287ca7e010";
          sha256 = "0fc1d60iw8iar2zcvkzwdvx0whkbw8p6ll0cry39nbkklzw85n1h";
        };
      };
    };
    "symfony/polyfill-php80" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-php80-1100343ed1a92e3a38f9ae122fc0eb21602547be";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-php80/zipball/1100343ed1a92e3a38f9ae122fc0eb21602547be";
          sha256 = "0kwk2qgwswsmbfp1qx31ahw3lisgyivwhw5dycshr5v2iwwx3rhi";
        };
      };
    };
    "symfony/polyfill-php81" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-polyfill-php81-e66119f3de95efc359483f810c4c3e6436279436";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/polyfill-php81/zipball/e66119f3de95efc359483f810c4c3e6436279436";
          sha256 = "0hg340da7m0yipj2bj5hxhd3mqidz767ivg7w85r8vwz3mr9k1p3";
        };
      };
    };
    "symfony/service-contracts" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-service-contracts-f040a30e04b57fbcc9c6cbcf4dbaa96bd318b9bb";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/service-contracts/zipball/f040a30e04b57fbcc9c6cbcf4dbaa96bd318b9bb";
          sha256 = "1i573rmajc33a9nrgwgc4k3svg29yp9xv17gp133rd1i705hwv1y";
        };
      };
    };
    "symfony/string" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-string-8d224396e28d30f81969f083a58763b8b9ceb0a5";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/string/zipball/8d224396e28d30f81969f083a58763b8b9ceb0a5";
          sha256 = "13bv53s2s7fvk064yx2xa0f5p9jh0slxc2pnrzp7m6jnqha6mlcy";
        };
      };
    };
    "symfony/yaml" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-yaml-4500fe63dc9c6ffc32d3b1cb0448c329f9c814b7";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/yaml/zipball/4500fe63dc9c6ffc32d3b1cb0448c329f9c814b7";
          sha256 = "02qgqsiizf0zb80v7mhkz7k3dwz5ybk9p48v4hk06qicccgzmmks";
        };
      };
    };
  };
  devPackages = {
    "brianium/paratest" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "brianium-paratest-3d81e35876f6497467310b123583cca6bd4c38f2";
        src = fetchurl {
          url = "https://api.github.com/repos/paratestphp/paratest/zipball/3d81e35876f6497467310b123583cca6bd4c38f2";
          sha256 = "1rj4gpkipczdnfcbds3zs3fgvkdzqp3vvb1aq157zcliyb8463hh";
        };
      };
    };
    "dealerdirect/phpcodesniffer-composer-installer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "dealerdirect-phpcodesniffer-composer-installer-fe390591e0241955f22eb9ba327d137e501c771c";
        src = fetchurl {
          url = "https://api.github.com/repos/Dealerdirect/phpcodesniffer-composer-installer/zipball/fe390591e0241955f22eb9ba327d137e501c771c";
          sha256 = "1xvx1qqf5nl630zsz8xq2p18lrzml7ydp4451vy1mrjgjhv5ifpv";
        };
      };
    };
    "myclabs/deep-copy" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "myclabs-deep-copy-776f831124e9c62e1a2c601ecc52e776d8bb7220";
        src = fetchurl {
          url = "https://api.github.com/repos/myclabs/DeepCopy/zipball/776f831124e9c62e1a2c601ecc52e776d8bb7220";
          sha256 = "181f3fsxs6s2wyy4y7qfk08qmlbvz1wn3mn3lqy42grsb8g8ym0k";
        };
      };
    };
    "pepakriz/phpstan-exception-rules" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "pepakriz-phpstan-exception-rules-0de69d8fc6e08b6ed79986eb19b9e23355b3d0f4";
        src = fetchurl {
          url = "https://api.github.com/repos/pepakriz/phpstan-exception-rules/zipball/0de69d8fc6e08b6ed79986eb19b9e23355b3d0f4";
          sha256 = "1c7fxvpmfg480rqwcpy4dgic069j8lr80xvwpr8wk6yw8pcliysf";
        };
      };
    };
    "phar-io/manifest" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phar-io-manifest-97803eca37d319dfa7826cc2437fc020857acb53";
        src = fetchurl {
          url = "https://api.github.com/repos/phar-io/manifest/zipball/97803eca37d319dfa7826cc2437fc020857acb53";
          sha256 = "107dsj04ckswywc84dvw42kdrqd4y6yvb2qwacigyrn05p075c1w";
        };
      };
    };
    "phar-io/version" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phar-io-version-bae7c545bef187884426f042434e561ab1ddb182";
        src = fetchurl {
          url = "https://api.github.com/repos/phar-io/version/zipball/bae7c545bef187884426f042434e561ab1ddb182";
          sha256 = "0hqmrihb4wv53rl3fg93wjldwrz79jyad5bv29ynbdklsirh7b2l";
        };
      };
    };
    "phpdocumentor/reflection-common" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpdocumentor-reflection-common-1d01c49d4ed62f25aa84a747ad35d5a16924662b";
        src = fetchurl {
          url = "https://api.github.com/repos/phpDocumentor/ReflectionCommon/zipball/1d01c49d4ed62f25aa84a747ad35d5a16924662b";
          sha256 = "1wx720a17i24471jf8z499dnkijzb4b8xra11kvw9g9hhzfadz1r";
        };
      };
    };
    "phpdocumentor/reflection-docblock" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpdocumentor-reflection-docblock-622548b623e81ca6d78b721c5e029f4ce664f170";
        src = fetchurl {
          url = "https://api.github.com/repos/phpDocumentor/ReflectionDocBlock/zipball/622548b623e81ca6d78b721c5e029f4ce664f170";
          sha256 = "1vs0fhpqk8s9bc0sqyfhpbs63q14lfjg1f0c1dw4jz97145j6r1n";
        };
      };
    };
    "phpdocumentor/type-resolver" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpdocumentor-type-resolver-a12f7e301eb7258bb68acd89d4aefa05c2906cae";
        src = fetchurl {
          url = "https://api.github.com/repos/phpDocumentor/TypeResolver/zipball/a12f7e301eb7258bb68acd89d4aefa05c2906cae";
          sha256 = "1kziz1qkq15d4gbxqpv8s5sy1bfd11djsvyqn27dcqx6rx0b3pkm";
        };
      };
    };
    "phpspec/prophecy" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpspec-prophecy-d86dfc2e2a3cd366cee475e52c6bb3bbc371aa0e";
        src = fetchurl {
          url = "https://api.github.com/repos/phpspec/prophecy/zipball/d86dfc2e2a3cd366cee475e52c6bb3bbc371aa0e";
          sha256 = "1v61xv4jg9sqkfkc52p8hca2283b1h7zkajqg4dfb1k78cqxr4js";
        };
      };
    };
    "phpstan/phpstan" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpstan-phpstan-b4d40f1d759942f523be267a1bab6884f46ca3f7";
        src = fetchurl {
          url = "https://api.github.com/repos/phpstan/phpstan/zipball/b4d40f1d759942f523be267a1bab6884f46ca3f7";
          sha256 = "0sbvbfcjyx6j4yaajy6iqsarf9iqa6pmz34p439kr8j5jcykadm2";
        };
      };
    };
    "phpstan/phpstan-doctrine" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpstan-phpstan-doctrine-5fe9a9b15707d9bc5178fa7cf0899e904d112ccd";
        src = fetchurl {
          url = "https://api.github.com/repos/phpstan/phpstan-doctrine/zipball/5fe9a9b15707d9bc5178fa7cf0899e904d112ccd";
          sha256 = "07bjsrnmarqgnglg0dvfi4gs3wc9azdwfzzik7g1jfch8ng9hagh";
        };
      };
    };
    "phpstan/phpstan-php-parser" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpstan-phpstan-php-parser-e140bc57f3bd5e8a4d45155556618a43736592e9";
        src = fetchurl {
          url = "https://api.github.com/repos/phpstan/phpstan-php-parser/zipball/e140bc57f3bd5e8a4d45155556618a43736592e9";
          sha256 = "1f0zfmyxwnpy284719103wfb5kax4vqh23n04c7lv0i74pqzlpm8";
        };
      };
    };
    "phpstan/phpstan-phpunit" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpstan-phpstan-phpunit-7c01ef93bf128b4ac8bdad38c54b2a4fd6b0b3cc";
        src = fetchurl {
          url = "https://api.github.com/repos/phpstan/phpstan-phpunit/zipball/7c01ef93bf128b4ac8bdad38c54b2a4fd6b0b3cc";
          sha256 = "0xdcmrl0h7ss90r4qbn11miack939kqgzgz24z6798pm71dplfj5";
        };
      };
    };
    "phpstan/phpstan-strict-rules" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpstan-phpstan-strict-rules-2b72e8e17d2034145f239126e876e5fb659675e2";
        src = fetchurl {
          url = "https://api.github.com/repos/phpstan/phpstan-strict-rules/zipball/2b72e8e17d2034145f239126e876e5fb659675e2";
          sha256 = "1k95lslybdc0bfkb6cjj03mmwanjlyxwq9fzknn9dj667iqw5j5m";
        };
      };
    };
    "phpunit/php-code-coverage" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpunit-php-code-coverage-d4c798ed8d51506800b441f7a13ecb0f76f12218";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/php-code-coverage/zipball/d4c798ed8d51506800b441f7a13ecb0f76f12218";
          sha256 = "022a6zbzl7nczr2ivq1viz7l9684v703gvq1amycg984li7i7ywz";
        };
      };
    };
    "phpunit/php-file-iterator" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpunit-php-file-iterator-aa4be8575f26070b100fccb67faabb28f21f66f8";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/php-file-iterator/zipball/aa4be8575f26070b100fccb67faabb28f21f66f8";
          sha256 = "0vxnrzwb573ddmiw1sd77bdym6jiimwjhcz7yvmsr9wswkxh18l6";
        };
      };
    };
    "phpunit/php-invoker" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpunit-php-invoker-5a10147d0aaf65b58940a0b72f71c9ac0423cc67";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/php-invoker/zipball/5a10147d0aaf65b58940a0b72f71c9ac0423cc67";
          sha256 = "1vqnnjnw94mzm30n9n5p2bfgd3wd5jah92q6cj3gz1nf0qigr4fh";
        };
      };
    };
    "phpunit/php-text-template" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpunit-php-text-template-5da5f67fc95621df9ff4c4e5a84d6a8a2acf7c28";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/php-text-template/zipball/5da5f67fc95621df9ff4c4e5a84d6a8a2acf7c28";
          sha256 = "0ff87yzywizi6j2ps3w0nalpx16mfyw3imzn6gj9jjsfwc2bb8lq";
        };
      };
    };
    "phpunit/php-timer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpunit-php-timer-5a63ce20ed1b5bf577850e2c4e87f4aa902afbd2";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/php-timer/zipball/5a63ce20ed1b5bf577850e2c4e87f4aa902afbd2";
          sha256 = "0g1g7yy4zk1bidyh165fsbqx5y8f1c8pxikvcahzlfsr9p2qxk6a";
        };
      };
    };
    "phpunit/phpunit" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "phpunit-phpunit-c814a05837f2edb0d1471d6e3f4ab3501ca3899a";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/phpunit/zipball/c814a05837f2edb0d1471d6e3f4ab3501ca3899a";
          sha256 = "1lj8l30c6pqn9jlbzkb1id7avpwf2icsqcav2zgxc8jrf1lp1wfz";
        };
      };
    };
    "sebastian/cli-parser" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-cli-parser-442e7c7e687e42adc03470c7b668bc4b2402c0b2";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/cli-parser/zipball/442e7c7e687e42adc03470c7b668bc4b2402c0b2";
          sha256 = "074qzdq19k9x4svhq3nak5h348xska56v1sqnhk1aj0jnrx02h37";
        };
      };
    };
    "sebastian/code-unit" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-code-unit-1fc9f64c0927627ef78ba436c9b17d967e68e120";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/code-unit/zipball/1fc9f64c0927627ef78ba436c9b17d967e68e120";
          sha256 = "04vlx050rrd54mxal7d93pz4119pas17w3gg5h532anfxjw8j7pm";
        };
      };
    };
    "sebastian/code-unit-reverse-lookup" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-code-unit-reverse-lookup-ac91f01ccec49fb77bdc6fd1e548bc70f7faa3e5";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/code-unit-reverse-lookup/zipball/ac91f01ccec49fb77bdc6fd1e548bc70f7faa3e5";
          sha256 = "1h1jbzz3zak19qi4mab2yd0ddblpz7p000jfyxfwd2ds0gmrnsja";
        };
      };
    };
    "sebastian/comparator" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-comparator-55f4261989e546dc112258c7a75935a81a7ce382";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/comparator/zipball/55f4261989e546dc112258c7a75935a81a7ce382";
          sha256 = "1d4bgf4m2x0kn3nw9hbb45asbx22lsp9vxl74rp1yl3sj2vk9sch";
        };
      };
    };
    "sebastian/complexity" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-complexity-739b35e53379900cc9ac327b2147867b8b6efd88";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/complexity/zipball/739b35e53379900cc9ac327b2147867b8b6efd88";
          sha256 = "1y4yz8n8hszbhinf9ipx3pqyvgm7gz0krgyn19z0097yq3bbq8yf";
        };
      };
    };
    "sebastian/diff" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-diff-3461e3fccc7cfdfc2720be910d3bd73c69be590d";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/diff/zipball/3461e3fccc7cfdfc2720be910d3bd73c69be590d";
          sha256 = "0967nl6cdnr0v0z83w4xy59agn60kfv8gb41aw3fpy1n2wpp62dj";
        };
      };
    };
    "sebastian/environment" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-environment-388b6ced16caa751030f6a69e588299fa09200ac";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/environment/zipball/388b6ced16caa751030f6a69e588299fa09200ac";
          sha256 = "022vn8zss3sm7hg83kg3y0lmjw2ak6cy64b584nbsgxfhlmf6msd";
        };
      };
    };
    "sebastian/exporter" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-exporter-d89cc98761b8cb5a1a235a6b703ae50d34080e65";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/exporter/zipball/d89cc98761b8cb5a1a235a6b703ae50d34080e65";
          sha256 = "1s8v0cbcjdb0wvwyh869y5f8d55mpjkr0f3gg2kvvxk3wh8nvvc7";
        };
      };
    };
    "sebastian/global-state" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-global-state-23bd5951f7ff26f12d4e3242864df3e08dec4e49";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/global-state/zipball/23bd5951f7ff26f12d4e3242864df3e08dec4e49";
          sha256 = "0hrh5knc2g5i288kh9lkwmr6hb7yav5m8i21piz8pfh7kc75czjw";
        };
      };
    };
    "sebastian/lines-of-code" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-lines-of-code-c1c2e997aa3146983ed888ad08b15470a2e22ecc";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/lines-of-code/zipball/c1c2e997aa3146983ed888ad08b15470a2e22ecc";
          sha256 = "0fay9s5cm16gbwr7qjihwrzxn7sikiwba0gvda16xng903argbk0";
        };
      };
    };
    "sebastian/object-enumerator" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-object-enumerator-5c9eeac41b290a3712d88851518825ad78f45c71";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/object-enumerator/zipball/5c9eeac41b290a3712d88851518825ad78f45c71";
          sha256 = "11853z07w8h1a67wsjy3a6ir5x7khgx6iw5bmrkhjkiyvandqcn1";
        };
      };
    };
    "sebastian/object-reflector" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-object-reflector-b4f479ebdbf63ac605d183ece17d8d7fe49c15c7";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/object-reflector/zipball/b4f479ebdbf63ac605d183ece17d8d7fe49c15c7";
          sha256 = "0g5m1fswy6wlf300x1vcipjdljmd3vh05hjqhqfc91byrjbk4rsg";
        };
      };
    };
    "sebastian/recursion-context" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-recursion-context-cd9d8cf3c5804de4341c283ed787f099f5506172";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/recursion-context/zipball/cd9d8cf3c5804de4341c283ed787f099f5506172";
          sha256 = "1k0ki1krwq6329vsbw3515wsyg8a7n2l83lk19pdc12i2lg9nhpy";
        };
      };
    };
    "sebastian/resource-operations" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-resource-operations-0f4443cb3a1d92ce809899753bc0d5d5a8dd19a8";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/resource-operations/zipball/0f4443cb3a1d92ce809899753bc0d5d5a8dd19a8";
          sha256 = "0p5s8rp7mrhw20yz5wx1i4k8ywf0h0ximcqan39n9qnma1dlnbyr";
        };
      };
    };
    "sebastian/type" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-type-b8cd8a1c753c90bc1a0f5372170e3e489136f914";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/type/zipball/b8cd8a1c753c90bc1a0f5372170e3e489136f914";
          sha256 = "05d36w28nr2i14nghzd279gvwwpcavcznb2h5bp2iy3rhaa14yjy";
        };
      };
    };
    "sebastian/version" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "sebastian-version-c6c1022351a901512170118436c764e473f6de8c";
        src = fetchurl {
          url = "https://api.github.com/repos/sebastianbergmann/version/zipball/c6c1022351a901512170118436c764e473f6de8c";
          sha256 = "1bs7bwa9m0fin1zdk7vqy5lxzlfa9la90lkl27sn0wr00m745ig1";
        };
      };
    };
    "slevomat/coding-standard" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "slevomat-coding-standard-696dcca217d0c9da2c40d02731526c1e25b65346";
        src = fetchurl {
          url = "https://api.github.com/repos/slevomat/coding-standard/zipball/696dcca217d0c9da2c40d02731526c1e25b65346";
          sha256 = "017mb08j9c6657nv9mkgy09qpy9540dxsvximgm1j1r1dzxnyj3j";
        };
      };
    };
    "squizlabs/php_codesniffer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "squizlabs-php_codesniffer-f268ca40d54617c6e06757f83f699775c9b3ff2e";
        src = fetchurl {
          url = "https://api.github.com/repos/squizlabs/PHP_CodeSniffer/zipball/f268ca40d54617c6e06757f83f699775c9b3ff2e";
          sha256 = "1836i2in2sryig5qvs8ldj6w5rv0yv7mzgxlrwhl6bn2pghgp2lz";
        };
      };
    };
    "symfony/process" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-process-38f26c7d6ed535217ea393e05634cb0b244a1967";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/process/zipball/38f26c7d6ed535217ea393e05634cb0b244a1967";
          sha256 = "0dmbcyms9rcxz5bj9bagxgdqffrcwpkh9qbij78d2bkc3wy21m6z";
        };
      };
    };
    "symfony/var-dumper" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "symfony-var-dumper-eaaea4098be1c90c8285543e1356a09c8aa5c8da";
        src = fetchurl {
          url = "https://api.github.com/repos/symfony/var-dumper/zipball/eaaea4098be1c90c8285543e1356a09c8aa5c8da";
          sha256 = "01dnfd6s37iy1w434kpfn11cadr8ag21kw6qskikk2h7w1fagp63";
        };
      };
    };
    "theseer/tokenizer" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "theseer-tokenizer-34a41e998c2183e22995f158c581e7b5e755ab9e";
        src = fetchurl {
          url = "https://api.github.com/repos/theseer/tokenizer/zipball/34a41e998c2183e22995f158c581e7b5e755ab9e";
          sha256 = "1za4a017kjb4rw2ydglip4bp5q2y7mfiycj3fvnp145i84jc7n0q";
        };
      };
    };
    "webmozart/assert" = {
      targetDir = "";
      src = composerEnv.buildZipPackage {
        name = "webmozart-assert-6964c76c7804814a842473e0c8fd15bab0f18e25";
        src = fetchurl {
          url = "https://api.github.com/repos/webmozarts/assert/zipball/6964c76c7804814a842473e0c8fd15bab0f18e25";
          sha256 = "17xqhb2wkwr7cgbl4xdjf7g1vkal17y79rpp6xjpf1xgl5vypc64";
        };
      };
    };
  };
in
composerEnv.buildPackage {
  inherit packages devPackages noDev;
  name = "serenata-serenata";
  src = ./.;
  executable = false;
  symlinkDependencies = false;
  meta = {
    homepage = "https://serenata.github.io";
    license = "AGPL-3.0-or-later";
  };
}
