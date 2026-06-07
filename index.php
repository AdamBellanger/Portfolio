<?php
// ═══════════════════════════════════════════════════════════════════════════════
//  FORMULAIRE DE CONTACT
// ═══════════════════════════════════════════════════════════════════════════════
$contact_status  = '';   // 'success' | 'error' | ''
$contact_values  = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
  $name    = trim(strip_tags($_POST['name']    ?? ''));
  $email   = trim(strip_tags($_POST['email']   ?? ''));
  $subject = trim(strip_tags($_POST['subject'] ?? ''));
  $message = trim(strip_tags($_POST['message'] ?? ''));

  $contact_values = compact('name', 'email', 'subject', 'message');

  if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $subject && $message) {
    $to      = 'contact@adambellanger.pro';
    $headers = implode("\r\n", [
      'From: Portfolio <' . $email . '>',
      'Reply-To: ' . $email,
      'MIME-Version: 1.0',
      'Content-Type: text/plain; charset=UTF-8',
    ]);
    $body = "Nom    : $name\nEmail  : $email\nSujet  : $subject\n\n$message";

    if (@mail($to, '[Portfolio] ' . $subject, $body, $headers)) {
      $contact_status = 'success';
      $contact_values = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    } else {
      // mail() non configuré (XAMPP local) → on simule le succès en dev
      $contact_status = 'success_dev';
      $contact_values = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
  } else {
    $contact_status = 'error';
  }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  VEILLE TECHNOLOGIQUE – Fetch RSS réel + cache 2 semaines + fallback statique
// ═══════════════════════════════════════════════════════════════════════════════

define('VEILLE_CACHE', __DIR__ . '/cache/veille_cache.json');
define('VEILLE_TTL',   7 * 24 * 3600); // 7 jours

// ── Mots-clés → catégorie, couleur, icône ─────────────────────────────────────
$veille_keywords = [
  'ransomware'             => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'cyberattaque'           => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'vulnérabilité'          => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'phishing'               => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'zero-day'               => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'cybersécurité'          => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'sécurité informatique'  => ['Cybersécurité', '#ff4757', 'fa-shield-alt'],
  'wifi'                   => ['Réseau',         '#00d4ff', 'fa-wifi'],
  'wi-fi'                  => ['Réseau',         '#00d4ff', 'fa-wifi'],
  'réseau'                 => ['Réseau',         '#00d4ff', 'fa-network-wired'],
  'routeur'                => ['Réseau',         '#00d4ff', 'fa-network-wired'],
  'cisco'                  => ['Réseau',         '#00d4ff', 'fa-network-wired'],
  'ipv6'                   => ['Réseau',         '#00d4ff', 'fa-network-wired'],
  'sd-wan'                 => ['Réseau',         '#00d4ff', 'fa-network-wired'],
  'virtualisation'         => ['Virtualisation', '#a855f7', 'fa-server'],
  'proxmox'                => ['Virtualisation', '#a855f7', 'fa-server'],
  'vmware'                 => ['Virtualisation', '#a855f7', 'fa-server'],
  'docker'                 => ['Virtualisation', '#a855f7', 'fa-server'],
  'conteneur'              => ['Virtualisation', '#a855f7', 'fa-server'],
  'hyperviseur'            => ['Virtualisation', '#a855f7', 'fa-server'],
  'cloud'                  => ['Cloud',          '#f97316', 'fa-cloud'],
  'azure'                  => ['Cloud',          '#f97316', 'fa-cloud'],
  'aws'                    => ['Cloud',          '#f97316', 'fa-cloud'],
  'saas'                   => ['Cloud',          '#f97316', 'fa-cloud'],
  'linux'                  => ['Systèmes',       '#22c55e', 'fa-linux'],
  'windows server'         => ['Systèmes',       '#22c55e', 'fa-windows'],
  'active directory'       => ['Systèmes',       '#22c55e', 'fa-server'],
  'debian'                 => ['Systèmes',       '#22c55e', 'fa-linux'],
  'intelligence artificielle' => ['IA & Infra',  '#e879f9', 'fa-robot'],
  'chatgpt'                => ['IA & Infra',     '#e879f9', 'fa-robot'],
  'llm'                    => ['IA & Infra',     '#e879f9', 'fa-robot'],
];

function veille_categorize(string $text, array $keywords): array {
  $lower = mb_strtolower($text);
  foreach ($keywords as $kw => $info) {
    if (mb_strpos($lower, $kw) !== false) return $info;
  }
  return ['Technologie', '#00d4ff', 'fa-microchip'];
}

function veille_fetch_rss(string $url): ?\SimpleXMLElement {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 2,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; Portfolio/1.0)',
      CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    return $data ? @simplexml_load_string($data) : null;
  }
  $data = @file_get_contents($url);
  return $data ? @simplexml_load_string($data) : null;
}

function veille_get_live(array $keywords): ?array {
  // Vérification du cache
  if (file_exists(VEILLE_CACHE) && (time() - filemtime(VEILLE_CACHE)) < VEILLE_TTL) {
    $c = json_decode(file_get_contents(VEILLE_CACHE), true);
    if (is_array($c) && count($c) >= 4) return array_slice($c, 0, 6);
  }

  $feeds = [
    ['url' => 'https://www.cert.ssi.gouv.fr/feed/',                                                   'label' => 'ANSSI CERT'],
    ['url' => 'https://www.it-connect.fr/feed/',                                                       'label' => 'IT-Connect'],
    ['url' => 'https://www.silicon.fr/feed',                                                           'label' => 'Silicon.fr'],
    ['url' => 'https://www.numerama.com/feed/',                                                        'label' => 'Numerama'],
    ['url' => 'https://www.01net.com/rss/actualites/',                                                 'label' => '01net'],
    ['url' => 'https://www.lemondeinformatique.fr/flux-rss/thematique/toutes-les-actualites/rss.xml',  'label' => 'LMI'],
    ['url' => 'https://www.zdnet.fr/feeds/rss/actualites/',                                            'label' => 'ZDNet France'],
  ];

  $articles = [];

  foreach ($feeds as $feed) {
    if (count($articles) >= 6) break;
    $xml = veille_fetch_rss($feed['url']);
    if (!$xml) continue;

    $items = isset($xml->channel->item) ? $xml->channel->item : [];
    foreach ($items as $item) {
      if (count($articles) >= 6) break;

      $title    = trim((string)$item->title);
      $desc_raw = strip_tags((string)($item->description ?? $item->summary ?? ''));
      $desc_raw = preg_replace('/\s+/', ' ', $desc_raw);
      $pub      = (string)($item->pubDate ?? $item->published ?? '');
      $link     = (string)($item->link ?? '');

      if (empty($title)) continue;

      $cat = veille_categorize($title . ' ' . $desc_raw, $keywords);

      $articles[] = [
        'accent'   => $cat[1],
        'icon'     => $cat[2],
        'category' => $cat[0],
        'date'     => $pub ? date('d/m/Y', strtotime($pub)) : date('d/m/Y'),
        'title'    => $title,
        'source'   => $feed['label'],
        'desc'     => mb_strlen($desc_raw) > 110 ? mb_substr($desc_raw, 0, 107) . '…' : $desc_raw,
        'desc_full'=> $desc_raw,
        'link'     => $link,
      ];
      break; // 1 seul article par source/feed
    }
  }

  if (count($articles) >= 4) {
    if (!is_dir(dirname(VEILLE_CACHE))) mkdir(dirname(VEILLE_CACHE), 0755, true);
    file_put_contents(VEILLE_CACHE, json_encode(array_slice($articles, 0, 6), JSON_UNESCAPED_UNICODE));
    return array_slice($articles, 0, 6);
  }
  return null;
}

// ── Fallback statique (si RSS indisponible) ────────────────────────────────────
$veille_fallback = [
  ['accent'=>'#ff4757','icon'=>'fa-shield-alt','category'=>'Cybersécurité','date'=>'2026','title'=>'Zero Trust Architecture — Le nouveau standard','source'=>'ANSSI','desc'=>'Approche "ne jamais faire confiance, toujours vérifier" qui remplace le modèle périmétrique. Indispensable avec la généralisation du télétravail et de l\'accès cloud hybride.','desc_full'=>'Le modèle Zero Trust impose une vérification systématique de chaque accès, même depuis le réseau interne. Il s\'appuie sur la micro-segmentation, l\'authentification forte (MFA) et le principe du moindre privilège. L\'ANSSI recommande son adoption progressive dans les SI critiques.','link'=>'https://www.ssi.gouv.fr'],
  ['accent'=>'#00d4ff','icon'=>'fa-network-wired','category'=>'Réseau','date'=>'2026','title'=>'Wi-Fi 7 (802.11be) débarque en entreprise','source'=>'ZDNet France','desc'=>'Débits théoriques jusqu\'à 46 Gbps, latence ultra-réduite et MLO (Multi-Link Operation). Impact direct sur les déploiements réseau en open-space et salles de réunion.','desc_full'=>'Le Wi-Fi 7 introduit la technologie MLO permettant d\'agréger plusieurs bandes fréquentielles simultanément (2,4/5/6 GHz). Associé à 4096-QAM et des canaux 320 MHz, il ouvre la voie aux usages temps réel (vidéoconférence 8K, AR/VR d\'entreprise) sans câblage.','link'=>''],
  ['accent'=>'#a855f7','icon'=>'fa-server','category'=>'Virtualisation','date'=>'2026','title'=>'Proxmox vs VMware : la migration s\'accélère','source'=>'LeMagIT','desc'=>'Depuis le rachat de VMware par Broadcom et la hausse des licences, de nombreuses entreprises migrent vers Proxmox VE. L\'écosystème Docker/LXC complète la solution open source.','desc_full'=>'Proxmox VE 8.x intègre nativement la haute disponibilité (cluster Corosync), la réplication ZFS et la gestion des conteneurs LXC. Sa gratuité et sa communauté active en font un choix stratégique pour les PME et administrations souhaitant s\'affranchir des coûts VMware.','link'=>''],
  ['accent'=>'#f97316','icon'=>'fa-cloud','category'=>'Cloud','date'=>'2026','title'=>'SASE & SD-WAN : la convergence réseau-sécurité','source'=>'Le Monde Informatique','desc'=>'Le modèle SASE fusionne les fonctions réseau (SD-WAN) et sécurité (CASB, SWG, ZTNA) dans une architecture cloud-native. Standard émergent pour les entreprises multi-sites.','desc_full'=>'SASE (Secure Access Service Edge) permet de délivrer des services réseau et de sécurité depuis le cloud, au plus près des utilisateurs. Il réduit la latence, simplifie la gestion et améliore la visibilité. Les grands acteurs : Palo Alto Prisma, Cisco Umbrella, Zscaler.','link'=>''],
  ['accent'=>'#22c55e','icon'=>'fa-linux','category'=>'Systèmes','date'=>'2026','title'=>'Durcissement Linux : les bonnes pratiques 2026','source'=>'ANSSI','desc'=>'Hardening d\'un serveur Debian/Ubuntu : désactivation des services inutiles, SSH par clé uniquement, fail2ban, auditd et SELinux/AppArmor. Checklist maintenue par l\'ANSSI.','desc_full'=>'Le guide ANSSI de configuration GNU/Linux recommande : mise à jour automatique des paquets de sécurité, restriction des droits sudo, chiffrement des partitions sensibles, monitoring des connexions avec auditd. Ces pratiques s\'appliquent directement aux serveurs Hetzner et on-premise.','link'=>'https://www.ssi.gouv.fr'],
  ['accent'=>'#e879f9','icon'=>'fa-robot','category'=>'IA & Infra','date'=>'2026','title'=>'AIOps : l\'IA s\'invite dans l\'administration réseau','source'=>'01net','desc'=>'Les outils AIOps analysent en temps réel les logs, métriques et flux réseau pour prédire les pannes, détecter les anomalies et automatiser les remédiations. Cisco et Juniper sont en première ligne.','desc_full'=>'AIOps combine machine learning et big data pour superviser les infrastructures à grande échelle. Cisco AI Network Analytics (intégré à DNA Center) et Juniper Mist utilisent des modèles ML pour corréler les événements réseau, réduire le MTTR et anticiper les dégradations avant qu\'elles n\'affectent les utilisateurs.','link'=>''],
];

// ── Résolution finale ──────────────────────────────────────────────────────────
$veille_now  = veille_get_live($veille_keywords);
$veille_live = ($veille_now !== null);
if (!$veille_live) $veille_now = $veille_fallback;

$update_label = file_exists(VEILLE_CACHE)
  ? date('d/m/Y', filemtime(VEILLE_CACHE))
  : date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Adam Bellanger – Portfolio</title>
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

  <!-- SCROLL PROGRESS BAR -->
  <div id="scroll-progress"></div>

  <!-- CURSEUR PERSONNALISÉ -->
  <div class="cursor-dot" id="cursor-dot"></div>
  <div class="cursor-ring" id="cursor-ring"></div>

  <!-- SCÈNE 3D (blobs + étoiles) -->
  <div class="three-container"></div>


  <!-- MODALES -->
  <div id="modal-portfolio" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Portfolio 3D</h2>
        <div class="modal-tags">
          <span class="modal-tag">Three.js</span>
          <span class="modal-tag">WebGL</span>
          <span class="modal-tag">HTML/CSS</span>
        </div>
        <p>
          Ce portfolio est ma première réalisation majeure en BTS SIO.
          L'objectif était de créer une expérience immersive qui se démarque
          des portfolios classiques.
        </p>
        <p>
          J'ai utilisé la librairie <strong>Three.js</strong> pour générer des formes organiques
          ("blobs") animées par des shaders GLSL personnalisés. Le site intègre un système
          de navigation fluide, un design "Glassmorphism" et des animations CSS avancées.
        </p>
        <a
          href="https://github.com/compteproadambellanger-gif/Portfolio"
          target="_blank"
          rel="noreferrer"
          class="btn-modal"
        >
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>

  <div id="modal-infra" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Architecture Réseau Cisco</h2>
        <div class="modal-tags">
          <span class="modal-tag">Cisco Packet Tracer</span>
          <span class="modal-tag">VLANs</span>
          <span class="modal-tag">Routage Inter-VLAN</span>
        </div>
        <p>
          Projet de conception d'une infrastructure réseau pour une PME de 300 employés répartis sur 3 étages.
        </p>
        <p>
          <strong>Réalisations :</strong><br>
          - Segmentation du réseau en 5 VLANs (Direction, RH, Tech, Invités, Serveurs).<br>
          - Configuration du protocole VTP et du routage "Router-on-a-stick".<br>
          - Mise en place de règles ACL pour sécuriser l'accès aux serveurs critiques.
        </p>
        <p><em>Documentation technique disponible prochainement.</em></p>
      </div>
    </div>
  </div>

  <div id="modal-univers" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Manchester City Universe</h2>
        <div class="modal-tags">
          <span class="modal-tag">PHP</span>
          <span class="modal-tag">MySQL</span>
          <span class="modal-tag">Chart.js</span>
          <span class="modal-tag">OAuth Google</span>
        </div>
        <p>
          Application web complète de gestion du club Manchester City développée
          dans le cadre du module Développement Web Backend. Le projet repose sur une architecture PHP/MySQL avec
          un système d'authentification multi-rôles.
        </p>
        <p>
          <strong>3 rôles distincts :</strong><br>
          – <span style="color:#00d4ff;font-weight:600;">Staff</span> — CRUD complet joueurs &amp; matchs, saisie de stats, dashboard avec graphiques Chart.js, gestion des utilisateurs.<br>
          – <span style="color:#a3e635;font-weight:600;">Joueur</span> — Dashboard personnalisé avec ses propres statistiques match par match (buts, passes, notes…).<br>
          – <span style="color:#fb923c;font-weight:600;">Supporter</span> — Zone fan : dernier match, forme récente, top buteur/passeur, intégration vidéo YouTube.
        </p>
        <p>
          <strong>Fonctionnalités clés :</strong>
          recherche temps réel + pagination, upload photo de profil/joueur,
          fiche individuelle avec graphique, mode sombre/clair, connexion Google OAuth.
        </p>
        <a
          href="https://github.com/compteproadambellanger-gif/ProjetUniversManCity"
          target="_blank"
          rel="noreferrer"
          class="btn-modal"
        >
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>

  <div id="modal-scripts" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Scripts d'Automatisation</h2>
        <div class="modal-tags">
          <span class="modal-tag">Bash</span>
          <span class="modal-tag">Python</span>
          <span class="modal-tag">Linux</span>
        </div>
        <p>
          Ensemble de scripts développés pour automatiser la maintenance des serveurs Linux (Debian/Ubuntu).
        </p>
        <ul>
          <li>Script de sauvegarde automatique des bases de données SQL vers un serveur distant.</li>
          <li>Script de surveillance des logs système avec alerte email en cas d'intrusion.</li>
          <li>Outil de déploiement rapide d'environnement LAMP.</li>
        </ul>
        <a href="https://github.com/compteproadambellanger-gif" class="btn-modal"><i class="fab fa-github"></i> Explorer sur GitHub</a>
      </div>
    </div>
  </div>
  
  <div id="modal-boutique" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Boutique en ligne Aesop</h2>
        <div class="modal-tags">
          <span class="modal-tag">PHP</span>
          <span class="modal-tag">MySQL</span>
          <span class="modal-tag">HTML/CSS</span>
          <span class="modal-tag">JavaScript</span>
          <span class="modal-tag">API stripe</span>
        </div>
        <p>
          Ensemble de scripts php / js pour créer une boutique en ligne.
        </p>
        <ul>
          <li>Création d'une base de données pour stocker les produits et les commandes.</li>
          <li>Création d'une interface pour ajouter des produits à la boutique.</li>
          <li>Nouveau design de la boutique Aesop</li>
        </ul>
        <a href="https://github.com/compteproadambellanger-gif/Aesop" class="btn-modal"><i class="fab fa-github"></i> Explorer sur GitHub</a>
      </div>
    </div>
  </div>

  <div id="modal-studio" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Studio Landing Pages</h2>
        <div class="modal-tags">
          <span class="modal-tag">PHP / MySQL</span>
          <span class="modal-tag">REACT.JS / JS</span>
          <span class="modal-tag">TAILWIND CSS</span>
          <span class="modal-tag">SERVER / VITE</span>
        </div>
        <p>
          Gros Projet de création de site web pour une entreprise de création de site web.
        </p>
        <ul>
          <li>création d'une base de donnée MySQL pour stocker les informations des clients et de leurs projets.</li>
          <li>création d'une interface pour ajouter des clients et de leurs projets.</li>
          <li>Nouveau design futuriste avec des animations fluides et des transitions rapides.</li>
        </ul>
        <a href="https://github.com/compteproadambellanger-gif/StudioLandingPages" class="btn-modal"><i class="fab fa-github"></i> Explorer sur GitHub</a>
      </div>
    </div>
  </div>

  <div id="modal-crypto" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>CryptoBourse Trading</h2>
        <div class="modal-tags">
          <span class="modal-tag">JavaScript</span>
          <span class="modal-tag">PHP</span>
          <span class="modal-tag">CSS</span>
          <span class="modal-tag">Client / Server</span>
        </div>
        <p>
          Simulateur de trading complet pour les cryptomonnaies et les marchés boursiers.
          Architecture client-serveur avec un frontend JavaScript et un backend PHP.
        </p>
        <p>
          <strong>Fonctionnalités :</strong><br>
          – Simulation d'achat/vente de cryptomonnaies en temps réel.<br>
          – Suivi de portefeuille et historique des transactions.<br>
          – Interface de trading avec graphiques et données de marché.
        </p>
        <a href="https://github.com/AdamBellanger/CryptoBourseTrading" target="_blank" rel="noreferrer" class="btn-modal">
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>

  <div id="modal-floatsniper" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>FloatSniper</h2>
        <div class="modal-tags">
          <span class="modal-tag">PHP</span>
          <span class="modal-tag">MySQL</span>
          <span class="modal-tag">APIs externes</span>
          <span class="modal-tag">CLI / Cron</span>
        </div>
        <p>
          Plateforme complète d'analyse et de sniping de skins multi-marchés (Steam, CSFloat, Skinport...).
          Le projet repose sur une architecture backend PHP robuste couplée à une base de données MySQL.
        </p>
        <p>
          <strong>Fonctionnalités :</strong><br>
          – Scans automatisés via CLI et alertes Discord en temps réel.<br>
          – Suivi des historiques de prix et Tracker personnel.<br>
          – Espace membre avec abonnements Stripe/PayPal/Coinbase, avec sécurité 2FA.<br>
          – Comparateur multi-marchés sollicitant de nombreuses API externes.
        </p>
        <a href="https://github.com/AdamBellanger/FloatSniper" target="_blank" rel="noreferrer" class="btn-modal">
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>
  <div id="modal-teledesk" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>TeleDesk</h2>
        <div class="modal-tags">
          <span class="modal-tag">Python</span>
          <span class="modal-tag">GMAO</span>
          <span class="modal-tag">Interface GUI</span>
          <span class="modal-tag">Bob! Desk</span>
        </div>
        <p>
          Outil d'import automatique d'équipements téléphonie et réseau vers <strong>Bob! Desk</strong>, le logiciel GMAO utilisé en entreprise.
        </p>
        <p>
          <strong>Fonctionnalités :</strong><br>
          – Interface graphique moderne avec drag &amp; drop de fichiers CSV/Excel.<br>
          – Parsing et validation automatique des données équipements.<br>
          – Import en masse vers la GMAO via API ou base de données.<br>
          – Logs d'import et gestion des erreurs en temps réel.
        </p>
        <a href="https://github.com/AdamBellanger/TeleDesk" target="_blank" rel="noreferrer" class="btn-modal">
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>

  <div id="modal-serveur" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>Serveur-Production</h2>
        <div class="modal-tags">
          <span class="modal-tag">Shell / Bash</span>
          <span class="modal-tag">Linux</span>
          <span class="modal-tag">Nginx</span>
          <span class="modal-tag">Hetzner</span>
        </div>
        <p>
          Collection de scripts Shell pour configurer, sécuriser et administrer un serveur Linux en production chez Hetzner.
        </p>
        <p>
          <strong>Contenu :</strong><br>
          – Scripts d'installation et configuration Nginx + PHP-FPM.<br>
          – Mise en place automatique de Nginx Proxy Manager.<br>
          – Durcissement SSH, pare-feu UFW et fail2ban.<br>
          – Scripts de sauvegarde et monitoring basique.
        </p>
        <a href="https://github.com/AdamBellanger/Serveur-Production" target="_blank" rel="noreferrer" class="btn-modal">
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>

  <div id="modal-bigfive" class="modal-overlay">
    <div class="modal-content">
      <button class="close-modal">&times;</button>
      <div class="modal-body">
        <h2>OutoffService Big Five</h2>
        <div class="modal-tags">
          <span class="modal-tag">JavaScript</span>
          <span class="modal-tag">HTML/CSS</span>
          <span class="modal-tag">Refonte web</span>
        </div>
        <p>
          Refonte complète du site web OutoffService Big Five. Redesign moderne, responsive et optimisé.
        </p>
        <p>
          <strong>Réalisations :</strong><br>
          – Nouveau design moderne avec animations CSS fluides.<br>
          – Interface responsive adaptée mobile et desktop.<br>
          – Optimisation des performances et de l'accessibilité.
        </p>
        <a href="https://github.com/AdamBellanger/OutoffServiceBigFive" target="_blank" rel="noreferrer" class="btn-modal">
          <i class="fab fa-github"></i> Explorer sur GitHub
        </a>
      </div>
    </div>
  </div>

  <!-- NAVIGATION -->
  <button class="nav-burger" id="nav-burger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
  <nav class="github-pill delayed-entry" id="main-nav">
    <div class="nav-marker"></div>
    <a href="#accueil" class="nav-link active">Accueil</a>
    <a href="#apropos" class="nav-link">Qui suis-je</a>
    <a href="#parcours" class="nav-link">Mon Parcours</a>
    <a href="#competences-1" class="nav-link">Logiciel &amp; Outils</a>
    <a href="#projets" class="nav-link">Projets</a>
    <a href="#competences" class="nav-link">Compétences</a>
    <a href="#veille" class="nav-link">Veille</a>
    <a href="#apprentissage" class="nav-link">Apprentissage</a>
    <a href="#contact" class="nav-link">Contact</a>
    <div class="nav-icons">
      <a href="https://ecoleiris.fr/campus/rouen" target="_blank" rel="noreferrer" title="École IRIS">
        <i class="fas fa-graduation-cap"></i>
      </a>
      <a href="https://www.linkedin.com/in/adam-bellanger-652919386/" target="_blank" rel="noreferrer" title="LinkedIn">
        <i class="fab fa-linkedin"></i>
      </a>
      <a href="https://github.com/AdamBellanger" target="_blank" rel="noreferrer" title="GitHub">
        <i class="fab fa-github"></i>
      </a>
    </div>
  </nav>

  <!-- ACCUEIL -->
  <section id="accueil" class="hero delayed-entry">
    <div id="hero-content">
      <h1 id="hero-title">Adam Bellanger</h1>
      <h3>PORTFOLIO</h3>
      <div class="hero-badge">
        Étudiant <strong>BTS SIO</strong> | Infrastructure &amp; Réseau
      </div>
      <div class="hero-location">
        <i class="fas fa-map-marker-alt"></i> Rouen, Normandie
      </div>
      <a href="cv/Adam%20BELLANGER.pdf" download="CV_Adam_Bellanger.pdf" class="btn-cv">
        <i class="fas fa-download"></i> Télécharger mon CV
      </a>
    </div>
  </section>

  <!-- À PROPOS -->
  <section id="apropos" class="delayed-entry">
    <div class="container">
      <div class="content-box apropos-box">
        <span class="section-label" style="text-align:center;display:block;">Qui suis-je</span>
        <p>
          Étudiant en <strong>BTS SIO option SISR</strong>, je développe un profil hybride entre
          <strong>réseau</strong>, <strong>téléphonie d'entreprise</strong> et <strong>développement web full-stack</strong>.
          Passionné par les systèmes d'information autant que par le code, j'aime construire des choses qui
          fonctionnent — que ce soit une infra réseau solide ou une application web déployée en production.
        </p>
        <p>
          Côté <strong>infrastructure</strong>, j'ai une expérience concrète sur des environnements réseau
          <strong>Huawei</strong> et <strong>Cisco</strong> : configuration de switchs, mise en place de
          <strong>VLANs</strong>, topologies d'entreprise et <strong>routage inter-VLAN</strong>. Je travaille
          également sur de la téléphonie d'entreprise dans ses différentes formes — <strong>Alcatel OXO</strong>,
          <strong>Centrex UnyCX</strong>, <strong>VoIP/SIP</strong> et lignes analogiques — avec une bonne
          compréhension des architectures de communication modernes et legacy.
        </p>
        <p>
          Côté <strong>développement</strong>, je maîtrise aussi bien le front-end que le back-end. En front, je
          conçois des interfaces modernes avec <strong>React</strong>, <strong>TypeScript</strong> et
          <strong>Tailwind CSS</strong>, en portant une attention particulière au design et à l'expérience
          utilisateur. En back, je construis des APIs et des services avec <strong>Node.js</strong> et
          <strong>PHP/MySQL</strong>. J'ai mis en production plusieurs projets personnels aboutis :
          <strong>FloatSniper</strong> (tracker de floats CS2 en temps réel) et
          <strong>polytrack.cloud</strong> (plateforme SaaS complète React + Node.js + PostgreSQL).
        </p>
        <p>
          Au-delà du code, j'administre mon propre <strong>serveur de production sous Ubuntu</strong> avec un
          stack <strong>Docker</strong> complet : reverse proxy via <strong>Nginx Proxy Manager</strong>,
          monitoring avec <strong>Grafana</strong> et <strong>Uptime Kuma</strong>, base de données
          <strong>PostgreSQL</strong>, automatisation de tâches via scripts <strong>Bash</strong> et workflows
          <strong>n8n</strong>. Je gère l'intégralité de la chaîne, du provisionnement serveur au déploiement
          applicatif, en passant par la gestion des certificats <strong>SSL</strong> et la surveillance des services.
        </p>
        <p>
          Ce double ancrage — <strong>systèmes et développement</strong> — me permet d'avoir une vision globale
          des projets tech, de l'infrastructure qui les supporte jusqu'à l'interface que l'utilisateur final utilise.
        </p>
      </div>
    </div>
  </section>

  <!-- PARCOURS -->
  <section id="parcours" class="delayed-entry">
    <div class="container">
      <div class="parcours-container">
        <span class="section-label">Mon parcours</span>
        <h2>Formation &amp; Expérience</h2>

        <div class="tl-switch" id="tl-switch">
          <div class="tl-switch-bg"></div>
          <button class="tl-switch-opt active" data-tab="formation">
            <i class="fas fa-graduation-cap"></i> Formation
          </button>
          <button class="tl-switch-opt" data-tab="experience">
            <i class="fas fa-briefcase"></i> Expérience
          </button>
        </div>

        <!-- FORMATION -->
        <div id="tl-formation" class="timeline-v2">
          <div class="tl-item current">
            <span class="tl-role">IRIS – École supérieure d'informatique</span>
            <span class="tl-company">BTS SIO, Services Informatiques aux Organisations</span>
            <span class="tl-period">2025 – 2027</span>
          </div>
          <div class="tl-item">
            <span class="tl-role">Campus La Chataigneraie</span>
            <span class="tl-company">Baccalauréat professionnel, système numérique</span>
            <span class="tl-period">Sept. 2022 – Juil. 2025</span>
            <span class="tl-mention"><i class="fas fa-star" style="font-size:.65rem"></i> Mention Bien</span>
          </div>
          <div class="tl-item">
            <span class="tl-role">Collège Saint Victrice</span>
            <span class="tl-company">Diplôme National du Brevet</span>
            <span class="tl-period">Sept. 2017 – Juil. 2022</span>
            <span class="tl-mention"><i class="fas fa-star" style="font-size:.65rem"></i> Mention Bien</span>
          </div>
        </div>

        <!-- EXPÉRIENCE -->
        <div id="tl-experience" class="timeline-v2" style="display:none">
          <div class="tl-item current">
            <span class="tl-role">Apprenti – Contrat en alternance</span>
            <span class="tl-company">Socacom <em style="color:rgba(255,255,255,0.35);font-weight:400">— Télécom Normandie</em></span>
            <span class="tl-period">Sept. 2025 – En cours · Rouen</span>
          </div>
          <div class="tl-item">
            <span class="tl-role">Stagiaire</span>
            <span class="tl-company">Socacom</span>
            <span class="tl-period">Janv. 2025 – Févr. 2025 · 2 mois · Rouen</span>
          </div>
          <div class="tl-item">
            <span class="tl-role">Stagiaire</span>
            <span class="tl-company">Socacom</span>
            <span class="tl-period">Sept. 2024 – Oct. 2024 · 2 mois · Rouen</span>
          </div>
          <div class="tl-item">
            <span class="tl-role">Stagiaire</span>
            <span class="tl-company">AJ PHONE <em style="color:rgba(255,255,255,0.35);font-weight:400">— Téléphonie d'entreprise</em></span>
            <span class="tl-period">Juin 2024 – Juil. 2024 · 2 mois · Rouen</span>
          </div>
          <div class="tl-item">
            <span class="tl-role">Stagiaire</span>
            <span class="tl-company">Socacom</span>
            <span class="tl-period">Nov. 2023 – Déc. 2023 · 2 mois</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- LOGICIELS & OUTILS -->
  <section id="competences-1" class="delayed-entry">
    <div class="glass-bubble-container">
      <div style="text-align: center;">
        <span class="section-label">Stack & outils</span>
        <h2 class="title-dev">Compétences Développement</h2>
      </div>
      <div class="custom-grid">
        <div class="grid-item" data-tooltip="Structuration des pages web">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/html5/html5-original.svg" alt="HTML5" class="tool-logo" />
          <span>HTML</span>
        </div>
        <div class="grid-item" data-tooltip="Mise en forme et animations">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/css3/css3-original.svg" alt="CSS3" class="tool-logo" />
          <span>CSS</span>
        </div>
        <div class="grid-item" data-tooltip="Interactivité côté client">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-original.svg" alt="JavaScript" class="tool-logo" />
          <span>JavaScript</span>
        </div>
        <div class="grid-item" data-tooltip="Scripts et automatisation">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-original.svg" alt="Python" class="tool-logo" />
          <span>Python</span>
        </div>
        <div class="grid-item" data-tooltip="Développement backend et base de données">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" alt="PHP" class="tool-logo" />
          <span>PHP</span>
        </div>
        <div class="grid-item" data-tooltip="Interfaces modernes et réactives">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/react/react-original.svg" alt="React" class="tool-logo" />
          <span>React</span>
        </div>
        <div class="grid-item" data-tooltip="Build tool ultra-rapide">
          <img src="https://cdn.simpleicons.org/vite/646CFF" alt="Vite" class="tool-logo" />
          <span>Vite</span>
        </div>
        <div class="grid-item" data-tooltip="Framework CSS utility-first">
          <img src="https://cdn.simpleicons.org/tailwindcss/06B6D4" alt="Tailwind" class="tool-logo" />
          <span>Tailwind CSS</span>
        </div>
        <div class="grid-item" data-tooltip="Base de données relationnelle">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/mysql/mysql-original.svg" alt="MySQL" class="tool-logo" />
          <span>MySQL</span>
        </div>
      </div>
      <div class="divider"></div>
      <div style="text-align: center;">
        <h2 class="title-tools">Mes outils / Logiciel</h2>
      </div>
      <div class="custom-grid">
        <div class="grid-item" data-tooltip="Retouche photo & graphisme">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/photoshop/photoshop-original.svg" alt="Photoshop" class="tool-logo" />
          <span>Photoshop</span>
        </div>
        <div class="grid-item" data-tooltip="Modélisation 3D & rendu">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/blender/blender-original.svg" alt="Blender" class="tool-logo" />
          <span>Blender</span>
        </div>
        <div class="grid-item" data-tooltip="IDE principal de développement">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vscode/vscode-original.svg" alt="VS Code" class="tool-logo" />
          <span>Visual Studio</span>
        </div>
        <div class="grid-item" data-tooltip="IDE Java & projets scolaires">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/eclipse/eclipse-original.svg" alt="Eclipse" class="tool-logo" />
          <span>Eclipse</span>
        </div>
        <div class="grid-item" data-tooltip="Versioning & collaboration">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/github/github-original.svg" alt="GitHub" class="tool-logo" style="filter:invert(1);" />
          <span>GitHub</span>
        </div>
        <div class="grid-item" data-tooltip="Assistant IA de développement en terminal">
          <img src="https://cdn.simpleicons.org/anthropic/ffffff" alt="Claude Code" class="tool-logo" />
          <span>Claude Code</span>
        </div>
        <div class="grid-item" data-tooltip="IDE nouvelle génération by Google">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/google/google-original.svg" alt="Antigravity" class="tool-logo" />
          <span>Antigravity</span>
        </div>
      </div>

      <div class="divider"></div>
      <div style="text-align: center;">
        <h2 class="title-tools" style="font-size:1.6rem;margin-bottom:1.5rem;">DevOps &amp; Infrastructure</h2>
      </div>
      <div class="custom-grid">
        <div class="grid-item" data-tooltip="Conteneurisation d'applications">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/docker/docker-original.svg" alt="Docker" class="tool-logo" />
          <span>Docker</span>
        </div>
        <div class="grid-item" data-tooltip="Automatisation de configuration serveurs">
          <img src="https://cdn.simpleicons.org/ansible/EE0000" alt="Ansible" class="tool-logo" />
          <span>Ansible</span>
        </div>
        <div class="grid-item" data-tooltip="Administration systèmes Linux">
          <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/linux/linux-original.svg" alt="Linux" class="tool-logo" />
          <span>Linux</span>
        </div>
        <div class="grid-item" data-tooltip="Hyperviseur open source, alternative VMware">
          <img src="https://cdn.simpleicons.org/proxmox/E57000" alt="Proxmox" class="tool-logo" />
          <span>Proxmox</span>
        </div>
        <div class="grid-item" data-tooltip="VPS & Cloud Server Hetzner">
          <img src="https://cdn.simpleicons.org/hetzner/D50C2D" alt="Hetzner" class="tool-logo" />
          <span>Hetzner</span>
        </div>
        <div class="grid-item" data-tooltip="Analyse réseau & capture de paquets">
          <img src="https://cdn.simpleicons.org/wireshark/1679A7" alt="Wireshark" class="tool-logo" />
          <span>Wireshark</span>
        </div>
        <div class="grid-item" data-tooltip="Configuration réseau Cisco, VLANs, routage">
          <img src="https://cdn.simpleicons.org/cisco/1BA0D7" alt="Cisco" class="tool-logo" />
          <span>Cisco</span>
        </div>
        <div class="grid-item" data-tooltip="Automatisation de workflows & intégrations">
          <img src="https://cdn.simpleicons.org/n8n/EA4B71" alt="n8n" class="tool-logo" />
          <span>n8n</span>
        </div>
        <div class="grid-item" data-tooltip="GMAO — Gestion maintenance et équipements">
          <i class="fas fa-tools" style="font-size:3rem;color:#00d4ff;margin-bottom:15px;display:block;"></i>
          <span>Bob! Desk</span>
        </div>
      </div>
    </div>
  </section>

  <!-- PROJETS -->
  <section id="projets" class="delayed-entry section-projets-fullwidth">
    <div class="projets-header">
      <span class="section-label">Réalisations</span>
      <h2>Mes Projets</h2>
      <p>Cliquez sur un projet pour voir les détails.</p>
    </div>

    <div class="carousel-wrapper">
      <button class="carousel-arrow carousel-prev" onclick="carouselMove(-1)" aria-label="Projet précédent">
        <i class="fas fa-chevron-left"></i>
      </button>

      <div class="carousel-track-container">
        <div class="carousel-track">

          <div class="bubble-project" onclick="openModal('modal-portfolio')">
            <div class="bubble-content">
              <i class="fas fa-globe project-icon-main"></i>
              <h4 class="project-title">Portfolio 3D</h4>
              <span class="project-tech">Three.js / WebGL</span>
            </div>
            <div class="bubble-glow"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-infra')">
            <div class="bubble-content">
              <i class="fas fa-network-wired project-icon-main"></i>
              <h4 class="project-title">Infra Cisco</h4>
              <span class="project-tech">VLAN / Routing</span>
            </div>
            <div class="bubble-glow" style="background: rgba(255, 107, 53, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-scripts')">
            <div class="bubble-content">
              <i class="fas fa-terminal project-icon-main"></i>
              <h4 class="project-title">Scripts Sys</h4>
              <span class="project-tech">Bash / Python</span>
            </div>
            <div class="bubble-glow" style="background: rgba(46, 204, 113, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-univers')">
            <div class="bubble-content">
              <i class="fas fa-futbol project-icon-main"></i>
              <h4 class="project-title">ProjetUnivers ManCity</h4>
              <span class="project-tech">PHP / MySQL / Chart.js</span>
            </div>
            <div class="bubble-glow" style="background: rgba(108, 171, 221, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-boutique')">
            <div class="bubble-content">
              <i class="fas fa-store project-icon-main"></i>
              <h4 class="project-title">Boutique en ligne</h4>
              <span class="project-tech">PHP / MySQL / JS</span>
            </div>
            <div class="bubble-glow" style="background: rgba(145, 73, 223, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-studio')">
            <div class="bubble-content">
              <i class="fas fa-rocket project-icon-main"></i>
              <h4 class="project-title">Studio Landing Pages</h4>
              <span class="project-tech">React / Tailwind / PHP</span>
            </div>
            <div class="bubble-glow" style="background: rgba(179, 182, 29, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-crypto')">
            <div class="bubble-content">
              <i class="fas fa-chart-line project-icon-main"></i>
              <h4 class="project-title">CryptoBourse Trading</h4>
              <span class="project-tech">JavaScript / PHP</span>
            </div>
            <div class="bubble-glow" style="background: rgba(247, 147, 26, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-floatsniper')">
            <div class="bubble-content">
              <i class="fas fa-crosshairs project-icon-main"></i>
              <h4 class="project-title">FloatSniper</h4>
              <span class="project-tech">PHP / MySQL / API</span>
            </div>
            <div class="bubble-glow" style="background: rgba(255, 71, 87, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-serveur')">
            <div class="bubble-content">
              <i class="fas fa-server project-icon-main"></i>
              <h4 class="project-title">Serveur Production</h4>
              <span class="project-tech">Shell / Nginx / Hetzner</span>
            </div>
            <div class="bubble-glow" style="background: rgba(239, 68, 68, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-teledesk')">
            <div class="bubble-content">
              <i class="fas fa-plug project-icon-main"></i>
              <h4 class="project-title">TeleDesk</h4>
              <span class="project-tech">Python / GMAO / GUI</span>
            </div>
            <div class="bubble-glow" style="background: rgba(59, 130, 246, 0.4);"></div>
          </div>

          <div class="bubble-project" onclick="openModal('modal-bigfive')">
            <div class="bubble-content">
              <i class="fas fa-paint-brush project-icon-main"></i>
              <h4 class="project-title">Big Five Refonte</h4>
              <span class="project-tech">JavaScript / HTML/CSS</span>
            </div>
            <div class="bubble-glow" style="background: rgba(168, 85, 247, 0.4);"></div>
          </div>

        </div>
      </div>

      <button class="carousel-arrow carousel-next" onclick="carouselMove(1)" aria-label="Projet suivant">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>

    <div class="carousel-drag-hint" id="carousel-hint">
      <div class="drag-track">
        <div class="drag-cursor"></div>
      </div>
      <span class="drag-label">glisser pour explorer</span>
    </div>

    <div class="carousel-dots">
      <span class="carousel-dot active" onclick="carouselGoTo(0)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(1)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(2)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(3)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(4)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(5)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(6)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(7)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(8)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(9)"></span>
      <span class="carousel-dot" onclick="carouselGoTo(10)"></span>
    </div>
      
  </section>

  <!-- COMPÉTENCES -->
  <section id="competences" class="delayed-entry">
    <div class="container">
      <div class="content-box">
        <span class="section-label" style="text-align:center;display:block;">Infra & Systèmes</span>
        <h2 style="text-align:center;">Compétences Techniques</h2>
        <div class="tech-grid-modern">

          <div class="tech-cat">
            <div class="tech-cat-header">
              <i class="fas fa-network-wired"></i>
              <span>Réseaux & Infrastructure</span>
            </div>
            <div class="tech-pills">
              <span class="tech-pill">Cisco IOS</span>
              <span class="tech-pill">Huawei</span>
              <span class="tech-pill">VLANs</span>
              <span class="tech-pill">Routage inter-VLAN</span>
              <span class="tech-pill">ACL</span>
              <span class="tech-pill">STP / OSPF</span>
              <span class="tech-pill">Solutions opérateurs</span>
              <span class="tech-pill">Câblage RJ45</span>
            </div>
          </div>

          <div class="tech-cat">
            <div class="tech-cat-header">
              <i class="fas fa-phone-alt"></i>
              <span>Téléphonie IP</span>
            </div>
            <div class="tech-pills">
              <span class="tech-pill">Alcatel OXO</span>
              <span class="tech-pill">VoIP / SIP</span>
              <span class="tech-pill">ToIP</span>
              <span class="tech-pill">Centrex</span>
              <span class="tech-pill">UnyCX</span>
              <span class="tech-pill">Trunk SIP</span>
              <span class="tech-pill">Postes IP</span>
            </div>
          </div>

          <div class="tech-cat">
            <div class="tech-cat-header">
              <i class="fas fa-server"></i>
              <span>Virtualisation</span>
            </div>
            <div class="tech-pills">
              <span class="tech-pill">Proxmox VE</span>
              <span class="tech-pill">VMware ESXi</span>
              <span class="tech-pill">Hyper-V</span>
              <span class="tech-pill">VirtualBox</span>
              <span class="tech-pill">LXC / Docker</span>
              <span class="tech-pill">Snapshots</span>
              <span class="tech-pill">Haute disponibilité</span>
            </div>
          </div>

          <div class="tech-cat">
            <div class="tech-cat-header">
              <i class="fas fa-desktop"></i>
              <span>Systèmes</span>
            </div>
            <div class="tech-pills">
              <span class="tech-pill">Windows Server</span>
              <span class="tech-pill">Linux Debian</span>
              <span class="tech-pill">Ubuntu</span>
              <span class="tech-pill">Active Directory</span>
              <span class="tech-pill">GPO</span>
              <span class="tech-pill">Supervision</span>
              <span class="tech-pill">Bash scripting</span>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- VEILLE TECHNOLOGIQUE -->
  <section id="veille" class="delayed-entry section-wide">
    <div class="container">
      <div class="content-box">
        <span class="section-label" style="text-align:center;display:block;">Domaines surveillés</span>
        <h2 style="text-align:center;">Veille Technologique</h2>
        <p style="color: rgba(255,255,255,0.55); margin-bottom: 0.5rem; font-size: 0.95rem; margin-top: -1rem; text-align:center;">
          Domaines surveillés activement dans le cadre de ma formation BTS SIO SISR.
        </p>
        <div style="text-align:center; margin-bottom:2rem;">
          <span class="veille-status-pill">
            <?php if ($veille_live): ?>
              <i class="fas fa-circle" style="color:#22c55e;font-size:0.5rem;vertical-align:middle;"></i>
              Flux RSS live &nbsp;·&nbsp; Mis à jour le <?= $update_label ?> &nbsp;·&nbsp; Refresh toutes les semaines
            <?php else: ?>
              <i class="fas fa-circle" style="color:#f97316;font-size:0.5rem;vertical-align:middle;"></i>
              Contenu de référence &nbsp;·&nbsp; Actualisation au prochain accès réseau
            <?php endif; ?>
          </span>
        </div>
        <div class="veille-grid">
          <?php foreach ($veille_now as $i => $a): ?>
          <div class="veille-card" style="--accent: <?= $a['accent'] ?>; cursor:pointer;" onclick="openVeilleModal(<?= $i ?>)">
            <div class="veille-header">
              <span class="veille-category" style="color:<?= $a['accent'] ?>;"><i class="fas <?= $a['icon'] ?>"></i> <?= $a['category'] ?></span>
              <span class="veille-date"><?= $a['date'] ?></span>
            </div>
            <h3 class="veille-title"><?= htmlspecialchars($a['title']) ?></h3>
            <p class="veille-source"><i class="fas fa-globe"></i> <?= $a['source'] ?></p>
            <p class="veille-desc"><?= htmlspecialchars($a['desc']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Modale Veille -->
        <div id="modal-veille" class="modal-overlay">
          <div class="modal-content">
            <button class="close-modal" onclick="closeVeilleModal()">&times;</button>
            <div class="modal-body">
              <span id="veille-modal-category" style="font-size:0.85rem;font-weight:600;letter-spacing:0.5px;"></span>
              <h2 id="veille-modal-title" style="margin-top:0.5rem;"></h2>
              <p id="veille-modal-source" style="color:rgba(255,255,255,0.4);font-size:0.85rem;margin-bottom:0.3rem;"></p>
              <p id="veille-modal-date" style="color:rgba(255,255,255,0.3);font-size:0.8rem;margin-bottom:1.5rem;"></p>
              <p id="veille-modal-desc" style="color:rgba(255,255,255,0.75);line-height:1.8;font-size:0.95rem;"></p>
              <a id="veille-modal-link" href="#" target="_blank" rel="noreferrer" class="btn-modal" style="display:none;margin-top:1.5rem;">
                <i class="fas fa-external-link-alt"></i> Lire l'article complet
              </a>
            </div>
          </div>
        </div>

        <script>
        const veilleData = <?= json_encode(array_values($veille_now), JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        function openVeilleModal(i) {
          const a = veilleData[i];
          if (!a) return;
          document.getElementById('veille-modal-category').innerHTML = '<i class="fas ' + a.icon + '"></i> ' + a.category;
          document.getElementById('veille-modal-category').style.color = a.accent;
          document.getElementById('veille-modal-title').textContent = a.title;
          document.getElementById('veille-modal-source').innerHTML = '<i class="fas fa-globe"></i> ' + a.source;
          document.getElementById('veille-modal-date').textContent = a.date;
          document.getElementById('veille-modal-desc').textContent = a.desc_full || a.desc;
          const linkEl = document.getElementById('veille-modal-link');
          if (a.link) { linkEl.href = a.link; linkEl.style.display = 'inline-block'; }
          else { linkEl.style.display = 'none'; }
          document.getElementById('modal-veille').classList.add('active');
          document.body.style.overflow = 'hidden';
        }
        function closeVeilleModal() {
          document.getElementById('modal-veille').classList.remove('active');
          document.body.style.overflow = '';
        }
        document.getElementById('modal-veille').addEventListener('click', function(e) {
          if (e.target === this) closeVeilleModal();
        });
        </script>
      </div>
    </div>
  </section>

  <!-- EN COURS D'APPRENTISSAGE -->
  <section id="apprentissage" class="delayed-entry">
    <div class="container">
      <div class="content-box">
        <span class="section-label" style="text-align:center;display:block;">En progression</span>
        <h2 style="text-align:center;">En cours d&apos;apprentissage</h2>
        <p style="text-align:center;color:rgba(255,255,255,0.45);margin-bottom:2.5rem;font-size:0.9rem;margin-top:-0.5rem;">
          Technologies que j&apos;explore et d&eacute;veloppe activement en ce moment.
        </p>

        <div class="skill-rings">

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="90" stroke="#ffd43b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <i class="fab fa-python ring-icon" style="color:#ffd43b;"></i>
                <span class="ring-pct">90%</span>
              </div>
            </div>
            <p class="ring-name">Python</p>
            <span class="ring-badge expert">Expert</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="60" stroke="#0db7ed" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <i class="fab fa-docker ring-icon" style="color:#0db7ed;"></i>
                <span class="ring-pct">60%</span>
              </div>
            </div>
            <p class="ring-name">Docker</p>
            <span class="ring-badge inter">Interm&eacute;diaire</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="90" stroke="#777bb4" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg" class="ring-icon-img" alt="PHP">
                <span class="ring-pct">90%</span>
              </div>
            </div>
            <p class="ring-name">PHP / SQL</p>
            <span class="ring-badge expert">Expert</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="45" stroke="#ff4757" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <i class="fas fa-shield-alt ring-icon" style="color:#ff4757;"></i>
                <span class="ring-pct">45%</span>
              </div>
            </div>
            <p class="ring-name">Cybers&eacute;curit&eacute;</p>
            <span class="ring-badge debutant">D&eacute;butant</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="25" stroke="#0078d4" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/azure/azure-original.svg" class="ring-icon-img" alt="Azure">
                <span class="ring-pct">25%</span>
              </div>
            </div>
            <p class="ring-name">Azure / Cloud</p>
            <span class="ring-badge debutant">D&eacute;butant</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="80" stroke="#61dafb" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <i class="fab fa-react ring-icon" style="color:#61dafb;"></i>
                <span class="ring-pct">80%</span>
              </div>
            </div>
            <p class="ring-name">React</p>
            <span class="ring-badge avance">Avanc&eacute;</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="90" stroke="#D50C2D" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <img src="https://cdn.simpleicons.org/hetzner/D50C2D" class="ring-icon-img" alt="Hetzner">
                <span class="ring-pct">90%</span>
              </div>
            </div>
            <p class="ring-name">Hetzner</p>
            <span class="ring-badge expert">Expert</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="65" stroke="#e63946" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <i class="fas fa-phone ring-icon" style="color:#e63946;"></i>
                <span class="ring-pct">65%</span>
              </div>
            </div>
            <p class="ring-name">Alcatel OXO</p>
            <span class="ring-badge inter">Interm&eacute;diaire</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="70" stroke="#4361ee" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <i class="fas fa-headset ring-icon" style="color:#4361ee;"></i>
                <span class="ring-pct">70%</span>
              </div>
            </div>
            <p class="ring-name">Centrex UnyCX</p>
            <span class="ring-badge inter">Interm&eacute;diaire</span>
          </div>

          <div class="ring-item">
            <div class="ring-wrap">
              <svg viewBox="0 0 36 36" class="ring-svg">
                <path class="ring-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                <path class="ring-fill" data-pct="50" stroke="#1BA0D7" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
              </svg>
              <div class="ring-center">
                <img src="https://cdn.simpleicons.org/cisco/1BA0D7" class="ring-icon-img" alt="Cisco">
                <span class="ring-pct">50%</span>
              </div>
            </div>
            <p class="ring-name">Cisco</p>
            <span class="ring-badge inter">Interm&eacute;diaire</span>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="delayed-entry">
    <div class="container">
      <div class="content-box">
        <span class="section-label" style="text-align:center;display:block;">Restons en contact</span>
        <h2 style="text-align:center;">Me Contacter</h2>
        <p style="text-align:center;color:rgba(255,255,255,0.45);margin-bottom:2.5rem;font-size:0.9rem;margin-top:-0.5rem;">
          Une question, une opportunité de stage ou juste un bonjour ?
        </p>

        <div class="contact-layout">

          <!-- Colonne gauche : infos -->
          <div class="contact-info">
            <p class="contact-info-text">
              Je suis disponible pour des opportunités d'alternance, de stage, ou simplement pour échanger sur un projet.
            </p>

            <div class="contact-info-links">
              <a href="mailto:contact@adambellanger.pro" class="cil-row">
                <i class="fas fa-envelope"></i>
                <span>contact@adambellanger.pro</span>
                <i class="fas fa-arrow-right cil-arrow"></i>
              </a>
              <a href="https://www.linkedin.com/in/adam-bellanger-652919386/" target="_blank" rel="noreferrer" class="cil-row">
                <i class="fab fa-linkedin"></i>
                <span>Adam Bellanger · LinkedIn</span>
                <i class="fas fa-arrow-right cil-arrow"></i>
              </a>
              <a href="https://github.com/AdamBellanger" target="_blank" rel="noreferrer" class="cil-row">
                <i class="fab fa-github"></i>
                <span>AdamBellanger · GitHub</span>
                <i class="fas fa-arrow-right cil-arrow"></i>
              </a>
              <a href="cv/Adam%20BELLANGER.pdf" download="CV_Adam_Bellanger.pdf" class="cil-row">
                <i class="fas fa-file-pdf"></i>
                <span>Télécharger mon CV PDF</span>
                <i class="fas fa-arrow-right cil-arrow"></i>
              </a>
            </div>
          </div>

          <!-- Colonne droite : formulaire -->
          <div class="contact-form-card">
            <?php if ($contact_status === 'success' || $contact_status === 'success_dev'): ?>
              <div class="contact-alert contact-alert--success" style="margin-bottom:1.25rem;">
                <i class="fas fa-check-circle"></i> Message envoyé ! Je vous répondrai dès que possible.
              </div>
            <?php elseif ($contact_status === 'error'): ?>
              <div class="contact-alert contact-alert--error" style="margin-bottom:1.25rem;">
                <i class="fas fa-exclamation-triangle"></i> Veuillez remplir tous les champs correctement.
              </div>
            <?php endif; ?>

            <form class="contact-form" method="POST" action="#contact">
              <div class="contact-row">
                <div class="contact-field">
                  <label for="cf-name">Nom</label>
                  <input type="text" id="cf-name" name="name" placeholder="Votre nom"
                         value="<?= htmlspecialchars($contact_values['name']) ?>" required autocomplete="name" />
                </div>
                <div class="contact-field">
                  <label for="cf-email">Email</label>
                  <input type="email" id="cf-email" name="email" placeholder="votre@email.com"
                         value="<?= htmlspecialchars($contact_values['email']) ?>" required autocomplete="email" />
                </div>
              </div>
              <div class="contact-field">
                <label for="cf-subject">Sujet</label>
                <input type="text" id="cf-subject" name="subject" placeholder="Ex : Proposition de stage, Question..."
                       value="<?= htmlspecialchars($contact_values['subject']) ?>" required />
              </div>
              <div class="contact-field">
                <label for="cf-message">Message</label>
                <textarea id="cf-message" name="message" rows="5"
                          placeholder="Votre message..."><?= htmlspecialchars($contact_values['message']) ?></textarea>
              </div>
              <button type="submit" name="contact_submit" class="btn-modal contact-submit">
                <i class="fas fa-paper-plane"></i> Envoyer le message
              </button>
            </form>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer id="simple-footer">
    <div class="footer-line"></div>
    <p>Made by Adam Bellanger 2025 &copy;</p>
  </footer>

  <!-- SCRIPTS -->
  <script type="importmap">
    {
      "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.182.0/build/three.module.js"
      }
    }
  </script>
  <script type="module" src="js/threescene.js"></script>
  <script src="js/ui.js"></script>
  <script>
  // Menu burger mobile
  (function() {
    var burger = document.getElementById('nav-burger');
    var nav    = document.getElementById('main-nav');
    if (!burger || !nav) return;
    burger.addEventListener('click', function() {
      nav.classList.toggle('nav-open');
      burger.classList.toggle('active');
    });
    // Ferme le menu au clic sur un lien
    nav.querySelectorAll('.nav-link').forEach(function(link) {
      link.addEventListener('click', function() {
        nav.classList.remove('nav-open');
        burger.classList.remove('active');
      });
    });
  })();

  // Animation rings SVG au scroll
  (function() {
    var rings = document.querySelectorAll('.ring-fill');
    if (!rings.length) return;
    var obs = new IntersectionObserver(function(entries) {
      entries.forEach(function(e) {
        if (!e.isIntersecting) return;
        e.target.style.strokeDasharray = e.target.dataset.pct + ', 100';
        obs.unobserve(e.target);
      });
    }, { threshold: 0.3 });
    rings.forEach(function(r) { obs.observe(r); });
  })();

  // Cache le hint carousel après la première interaction
  (function() {
    const hint = document.getElementById('carousel-hint');
    if (!hint) return;
    function hide() {
      hint.style.opacity = '0';
      hint.style.pointerEvents = 'none';
      /* on garde la hauteur pour pas faire sauter le layout */
    }
    document.querySelectorAll('.carousel-arrow, .carousel-dot').forEach(el => {
      el.addEventListener('click', hide, { once: true });
    });
    document.querySelector('.carousel-track')?.addEventListener('mousedown', hide, { once: true });
  })();

  (function() {
    function initSwitch() {
      const sw   = document.getElementById('tl-switch');
      if (!sw) return;
      const bg   = sw.querySelector('.tl-switch-bg');
      const opts = Array.from(sw.querySelectorAll('.tl-switch-opt'));
      let current = 0;

      function moveBg(idx) {
        const btn = opts[idx];
        bg.style.width  = btn.offsetWidth  + 'px';
        bg.style.left   = btn.offsetLeft   + 'px';
        bg.style.height = btn.offsetHeight + 'px';
        bg.style.top    = btn.offsetTop    + 'px';
      }

      function setTab(idx) {
        if (idx === current) return;
        current = idx;
        opts.forEach((o, i) => o.classList.toggle('active', i === idx));
        moveBg(idx);
        document.getElementById('tl-formation').style.display  = idx === 0 ? 'block' : 'none';
        document.getElementById('tl-experience').style.display = idx === 1 ? 'block' : 'none';
      }

      // Init bg position
      setTimeout(() => moveBg(0), 60);
      window.addEventListener('resize', () => moveBg(current));

      // Clic
      opts.forEach((opt, i) => opt.addEventListener('click', () => setTab(i)));

      // Drag souris gauche↔droite
      let dragX = 0, dragging = false;
      sw.addEventListener('mousedown', e => { dragX = e.clientX; dragging = true; e.preventDefault(); });
      document.addEventListener('mouseup', e => {
        if (!dragging) return;
        dragging = false;
        const diff = e.clientX - dragX;
        if (diff >  55) setTab(1);
        if (diff < -55) setTab(0);
      });

      // Swipe tactile
      sw.addEventListener('touchstart', e => { dragX = e.touches[0].clientX; }, { passive: true });
      sw.addEventListener('touchend',   e => {
        const diff = e.changedTouches[0].clientX - dragX;
        if (diff >  55) setTab(1);
        if (diff < -55) setTab(0);
      }, { passive: true });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initSwitch);
    } else {
      initSwitch();
    }
  })();
  </script>

  <!-- SCROLL-TO-TOP -->
  <button id="scroll-top-btn" title="Retour en haut">
    <i class="fas fa-chevron-up"></i>
  </button>

</body>
</html>
