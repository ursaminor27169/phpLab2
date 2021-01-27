<?php
    $MAX_DEEP = 8; // инициируем максимальную глубину обхода сайта

    function getHTMLcode( $url )
    {
        try
        {
            if( !$ch = curl_init( $url ) ) // инициализируем сеанс, если невозможно
                throw new Exception(); // то генерируем исключительную ситуацию
            curl_setopt($ch, CURLOPT_HEADER, 0); // устанавливаем параметры
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $ret = curl_exec( $ch ); // выполненияем запрос
            curl_close($ch); // завершаем сеанс
            return $ret; // возвращам результат
        }
        catch(Exception $e) // если не удалось использовать cURL
        {
            return @file_get_contents( $url ); // используем стандартную
        }
    }

    function getALLtag( $text, $tag )
    {
    // формируем шаблон для тега
        $pattern='#<'.$tag.'([\s]+[^>]*|)>(.*?)<\/'.$tag.'>#i';
    // получаем массив со строками соответствующими второму этапу задачи
        preg_match_all( $pattern, $text, $ret, PREG_SET_ORDER );
        foreach( $ret as $k=>$v ) // для всех найденых тегов
        {
            if( $tag == 'a' ) // если мы искали входдения тега <a>
            {
                $href = ''; // определяем адрес ссылки
                preg_match( '#(.*)href="(.*?)"#i', $v[1], $arr);
                if( $arr ) // если успешно
                    $href = $arr[2]; // сохраняем адрес в переменной
    // возвращаем адрес и текст ссылки
                $ret[$k] = array( 'href'=>$href, 'text'=>$v[2]);
            }
            else // иначе
                $ret[$k] = array( 'text' => $v[2] ); // возвращаем текст тега
        }
        return $ret; // возвращаем массив с текстами тегов
    }

    function getLINKtype( $href, $url )
    {
    // если в адресе ссылки нет протокола – ссылка локальная
        if( strops('://', $href)===false ) return 3;
    // выедляем в ссылке имя сервера
        $domen = parse_url($href, PHP_URL_HOST);
    // если имя сервера в ссылке равно текущему имени сервера
        if($domen == parse_url($url, PHP_URL_HOST) )
            return 2; // глобальная ссылка на этот же сайт
        return 1; // иначе ссылку считаем глобальной
    }
    function li_echo($arr) {
        foreach($arr as $a) {
            echo '<li><p>'.$a.'</p></li>';
        }
    }

    function getINFO( $url, $deep )
        {
            global $MAX_DEEP; // читаем максимальную глубину из внешней переменной
            if( $deep>$MAX_DEEP ) return; // если превышен максимальный уровень вложности
            $code = getHTMLcode( $url ); // определяем html-код страницы
            echo '<ul><p>URL: '.$url.'</p><br>';
            $titles = getALLtag( $code, 'title' ); // получаем массивы
            li_echo($titles);
            $descriptions = getALLtag( $code, description); // с информацией
            li_echo($descriptions);
            $keywords = getALLtag( $code, 'keywords' );
            li_echo($keywords);
            $h1 = getALLtag( $code, 'h1' );
            li_echo($h1);
            $h2 = getALLtag( $code, 'h2' );
            li_echo($h2);
            $a = getALLtag( $code, 'a' );
            $locals = array();
            $outsides = array();
            $insides = array();

            foreach($a as $link) {// для всех страниц по ссылкам
                $type = getLINKtype($link);
                if ($type == 1) array_push($outsides);
                elseif ($type == 2) array_push($insides);
                else array_push($locals);
            }
            echo '<li><ul><p>Локальные ссылки:</p><br>';
            li_echo($locals);
            echo '</ul></li>';
            echo '<li><ul><p>Внешние ссылки:</p><br>';
            li_echo($outsides);
            echo '</ul></li>';
            echo '<li><ul><p>Внутренние ссылки:</p><br>';
            foreach ($insides as $link) {
                echo '<li><ul><p>URL: '.$link.'</p><br>';
                getINFO( $link['href'], $deep+1 );
            }
            echo '</ul></li>';
            echo '</ul>';
        }


    if(isset($_POST['link'])) {
        getINFO( $_POST['link'], 1 );
        echo '<a href="/">Назад</a>';
    } else {
        echo '<form id="main" name="main" method="post" action="">
              <input type="text" placeholder="Введите ссылку" name="link" id="link" >
              <input id="submit" type="submit" value="Анализ">';
    }
?>



