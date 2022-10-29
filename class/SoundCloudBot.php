<?php

use \TelegramPhp\Methods;
use \Doctrine\ORM\EntityManager;
use \Doctrine\ORM\ORMSetup;

class SoundCloudBot {

    private $sc;
    private $entityManager;

    const TEXTOS = [
        'pt' => [
            'start' => "😉 Olá, %s!\nBaixe suas músicas favoritas do SoundCloud diretamente pelo Telegram, você só precisa enviar o link\n\nEx: https://soundcloud.com/vintageculturemusic/vintage-culture-constantinne-felten-eyes-out-may-11th",
            'help' => "😀 Para download envie o link da sua música ex: <em>https://soundcloud.com/andmeandyou/westbam-feat-richard-butler-you-need-the-drugs-me-remix</em>\n\n• Você também pode compartilhar diretamente do site ou app do Soundcloud",
            'erro' => "😮 Faiô! Não foi possível baixar essa música, ela pode estar protegida contra download!",
            'erro_tamanho' => "🤷‍♂️ Essa música é muito grande para ser enviada!"
        ],
        'es' => [
            'start' => "😉 ¡Hola, %s!\nDescarga tus canciones favoritas de SoundCloud directamente por Telegram, solo necesitas enviar el enlace\n\nEj: https://soundcloud.com/vintageculturemusic/vintage-culture-constantinne-felten-eyes-out-may-11th",
            'help' => "😀 Para descargar envía el enlace de tu canción ej: <em>https://soundcloud.com/andmeandyou/westbam-feat-richard-butler-you-need-the-drugs-me-remix</em>\n\n• También puede compartir directamente desde el sitio web o la aplicación de Soundcloud",
            'erro' => "😮 Esta canción no se pudo descargar, ¡es posible que esté protegida contra descargas!",
            'erro_tamanho' => "🤷‍♂️ ¡Esta canción es demasiado grande para enviarla!"
        ],
        'en' => [
            'start' => "😉 Hello, %s!\nDownload your favorite songs from SoundCloud directly by Telegram, you just need to send the link\n\nEx: https://soundcloud.com/vintageculturemusic/vintage-culture-constantinne-felten-eyes-out-may-11th",
            'help' => "😀 For download send the link of your song ex: <em>https://soundcloud.com/andmeandyou/westbam-feat-richard-butler-you-need-the-drugs-me-remix</em>\n\n• You can also share directly from the Soundcloud website or app",
            'erro' => "😮 This song could not be downloaded, it may be download protected!",
            'erro_tamanho' => "🤷‍♂️ This song is too big to send!",
        ]
    ];

    public function __construct ()
    {
        $config = ORMSetup::createAttributeMetadataConfiguration ([
            __DIR__.'/../'
        ]);

        $this->entityManager = EntityManager::create ([
            'url' => 'mysql://root:@localhost/scdownbot'
        ], $config);

        $this->sc = new SoundCloudPHP;
    }

    /**
     * Response command /start
     * 
     * @param \TelegramPhp $bot
     * 
     */
    public function start ($bot)
    {

        Methods::sendMessage ([
            'chat_id' => $bot->getChatId (),
            'text' => $this->messageLang ($bot->getLanguageCode (), 'start', $bot->getFirstName ()),
            'parse_mode' => 'html',
            'disable_web_page_preview' => true
        ]);

        $user = $this->entityManager->getRepository ('Usuarios')->findOneBy (['id_telegram' => $bot->getUserId ()]);

        if ($user == null)
        {

            $usuarios = new \Usuarios ();
            $usuarios->setId_telegram ($bot->getUserId ());
    
            $this->entityManager->persist ($usuarios);
            $this->entityManager->flush ();

        }

    }
    
    /**
     * Response command /help
     * 
     * @param \TelegramPhp $bot
     * 
     */
    public function help ($bot)
    {

        Methods::sendMessage ([
            'chat_id' => $bot->getChatId (),
            'text' => $this->messageLang ($bot->getLanguageCode (), 'help'),
            'parse_mode' => 'html',
            'disable_web_page_preview' => true
        ]);

    }

    /**
     * 
     * Download music and send to user
     * 
     * @param \TelegramPhp $bot
     * @param array $data
     * 
     * 
     */
    public function downloadMusic ($bot, $data)
    {

        $link_music = $this->parseSoundCloudLinks ($data [0]);
        $info = $this->sc->getMusicInfo ($link_music);
        $music = '';
        
        foreach ($info ['media']['transcodings'] as $types){
            if ($types ['format']['protocol'] == 'progressive') $music = $this->sc->getMusic ($types ['url']);
        }
        
        if ($music == null){

            Methods::sendMessage ([
                'chat_id' => $bot->getUserId (),
                'text' => $this->messageLang ($bot->getLanguageCode (), 'erro'),
                'parse_mode' => 'html',
            ]);

        }else {
            
            $titulo = $info ['title'];

            $username = $info ['user']['username'];
            $user_permalink = $info ['user']['permalink_url'];

            $lancamento = date ('d/m/Y', strtotime ($info ['created_at']));
            $artwork = str_replace ('large', 'crop', $info ['artwork_url']) ?? '';

            $text = "🎧 <a href=\"$user_permalink\">{$username}</a> - <b>{$titulo}</b>\n\n";
            $text .= "😉 @SCDownbot\n📅 {$lancamento}";

            Methods::sendChatAction ([
                'chat_id' => $bot->getUserId (),
                'action' => 'upload_voice'
            ]);

            $cache = $this->entityManager->getRepository ('Cache')->findOneBy (['url' => $link_music]);

            if ($cache == null){

                // envia musica e faz cache

                $envio = Methods::sendAudio ([
                    'chat_id' => $bot->getUserId (),
                    'caption' => $text,
                    'audio' => new CURLStringFile (file_get_contents ($music), $titulo, 'audio/mpeg'),
                    'thumb' => (empty ($artwork)) ? '' : new CURLStringFile (file_get_contents ($artwork), 'thumb', 'image/jpeg'),
                    'title' => "{$titulo} - @SCDownbot",
                    'performer' => $username,
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true
                ]);

                if (isset ($envio ['error_code'])){

                    Methods::sendMessage ([
                        'chat_id' => $bot->getUserId (),
                        'text' => $this->messageLang ($bot->getLanguageCode (), 'erro_tamanho')
                    ]);

                }else {
                    
                    $file_id = $envio ['result']['audio']['file_id'];
    
                    $music_cache = new Cache ();
                    $music_cache->setUrl ($link_music);
                    $music_cache->setFile_id ($file_id);
    
                    $this->entityManager->persist ($music_cache);
                    $this->entityManager->flush ();
                    
                }

            }else {
                
                // envia cache já salvo no banco de dados

                Methods::sendAudio ([
                    'chat_id' => $bot->getUserId (),
                    'caption' => $text,
                    'audio' => $cache->getFile_id (),
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true
                ]);

            }

        }

    }

    public function messageLang ($lang, $comando, ...$values)
    {
        @list ($lang) = @explode ('-', $lang);
        $lang = (!isset (self::TEXTOS [$lang])) ? 'en' : $lang;

        return sprintf (self::TEXTOS [$lang][$comando], ...$values);
    }

    function parseSoundCloudLinks ($link)
    {
        $url_soundcloud = $link;

        if (strpos ($url_soundcloud, 'm.soundcloud') !== false){
            return str_replace ('m.soundcloud', 'soundcloud', $url_soundcloud);
        }
        
        $context = stream_context_create ([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $url_soundcloud = get_headers ($url_soundcloud, true, $context)['Location'] ?? $url_soundcloud;

        $url_final = parse_url ($url_soundcloud);
        
        return "{$url_final ['scheme']}://{$url_final ['host']}{$url_final ['path']}";
    }

}