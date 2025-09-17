<?php

use App\Enums\PlatformRole;

return [

    'sections' => [

        /*
        |----------------------------------------------------------------------
        | Principal
        |----------------------------------------------------------------------
        */
        'principal' => [
            'title' => 'Principal',
            'icon'  => 'ki-home-2',
            'icon_paths' => 2,
            'entries' => [
                [
                    'key'         => 'bulletin-hebdomadaire',
                    'title'       => 'Bulletin Hebdomadaire',
                    'icon'        => 'ki-document',
                    'icon_paths'  => 2,
                    'roles'       => [],
                    'description' => 'Le bulletin hebdomadaire est la page d\'accueil de l\'application. Il regroupe toutes les informations importantes de la semaine : services liturgiques, activites communautaires et reflexions spirituelles.',
                    'steps' => [
                        'Cliquez sur "Bulletin Hebdomadaire" dans le menu lateral ou accedez a la page d\'accueil.',
                        'Le bulletin de la semaine en cours s\'affiche automatiquement.',
                        'Consultez les services liturgiques, les activites prevues et les reflexions spirituelles.',
                        'Utilisez les fleches de navigation pour consulter les bulletins des semaines precedentes.',
                    ],
                    'tips' => [
                        'Le bulletin est mis a jour chaque semaine par l\'equipe de communication.',
                        'Si aucun bulletin n\'est disponible, un message d\'information s\'affiche.',
                    ],
                ],
                [
                    'key'         => 'tableau-de-bord',
                    'title'       => 'Tableau de bord',
                    'icon'        => 'ki-home-2',
                    'icon_paths'  => 2,
                    'roles'       => [],
                    'description' => 'Le tableau de bord offre une vue d\'ensemble personnalisee : vos prochaines reservations, vos demandes en cours, les repas a venir, les anniversaires de la semaine et le menu du jour.',
                    'steps' => [
                        'Cliquez sur "Tableau de bord" dans le menu lateral.',
                        'Consultez vos reservations a venir dans la section "Mes reservations".',
                        'Voyez vos demandes en cours dans la section "Mes demandes".',
                        'Decouvrez le menu du jour et les prochains repas du dimanche.',
                    ],
                    'tips' => [
                        'Les administrateurs voient des statistiques supplementaires (nombre d\'utilisateurs, reservations en attente, etc.).',
                        'Les actions rapides en haut du tableau de bord permettent d\'acceder directement aux fonctions les plus utilisees.',
                    ],
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Services
        |----------------------------------------------------------------------
        */
        'services' => [
            'title' => 'Services',
            'icon'  => 'ki-element-11',
            'icon_paths' => 3,
            'entries' => [
                [
                    'key'         => 'planning-vehicules',
                    'title'       => 'Planning Vehicules',
                    'icon'        => 'ki-map',
                    'icon_paths'  => 3,
                    'roles'       => [],
                    'description' => 'Le planning vehicules affiche un calendrier interactif de toutes les reservations. Vous pouvez voir la disponibilite des vehicules et creer une nouvelle reservation directement depuis cette vue.',
                    'steps' => [
                        'Cliquez sur "Planning Vehicules" dans le menu lateral.',
                        'Selectionnez la date souhaitee sur le calendrier.',
                        'Les reservations existantes s\'affichent sous forme de blocs colores.',
                        'Cliquez sur un creneau libre pour creer une nouvelle reservation.',
                        'Remplissez le formulaire (vehicule, horaires, motif) et validez.',
                    ],
                    'tips' => [
                        'Les couleurs des blocs indiquent le statut : en attente, approuvee ou refusee.',
                        'Le planning se met a jour en temps reel grace aux notifications WebSocket.',
                        'Vous ne pouvez reserver que les types de vehicules auxquels vous avez acces.',
                    ],
                ],
                [
                    'key'         => 'mes-reservations',
                    'title'       => 'Mes Reservations',
                    'icon'        => 'ki-car-2',
                    'icon_paths'  => 5,
                    'roles'       => [],
                    'description' => 'Cette page liste toutes vos reservations de vehicules (passees et a venir). Vous pouvez modifier ou annuler vos reservations en attente.',
                    'steps' => [
                        'Cliquez sur "Mes Reservations" dans le menu lateral.',
                        'Consultez la liste de vos reservations avec leur statut.',
                        'Cliquez sur une reservation pour voir ses details.',
                        'Utilisez les boutons "Modifier" ou "Annuler" pour les reservations en attente.',
                    ],
                    'tips' => [
                        'Une reservation approuvee ne peut plus etre modifiee, mais peut etre annulee.',
                        'Vous recevez une notification lorsque votre reservation est approuvee ou refusee.',
                    ],
                ],
                [
                    'key'         => 'menu-semaine',
                    'title'       => 'Menu de la semaine',
                    'icon'        => 'ki-coffee',
                    'icon_paths'  => 2,
                    'roles'       => [],
                    'description' => 'Consultez les menus prevus pour chaque jour de la semaine (midi et soir). Les menus sont mis a jour chaque semaine par l\'administrateur.',
                    'steps' => [
                        'Cliquez sur "Menu de la semaine" dans le menu lateral.',
                        'Les menus du jour sont mis en evidence en haut de la page.',
                        'Faites defiler pour voir les menus des autres jours.',
                    ],
                    'tips' => [
                        'Le menu du jour est egalement visible sur le tableau de bord.',
                        'Les menus peuvent etre desactives par l\'administrateur si non disponibles.',
                    ],
                ],
                [
                    'key'         => 'repas-dimanche',
                    'title'       => 'Repas du Dimanche',
                    'icon'        => 'ki-gift',
                    'icon_paths'  => 2,
                    'roles'       => [],
                    'description' => 'Inscrivez-vous aux repas communautaires du dimanche. Indiquez le nombre de personnes (adultes et enfants) et gerez votre inscription.',
                    'steps' => [
                        'Cliquez sur "Repas du Dimanche" dans le menu lateral.',
                        'Consultez les prochains repas disponibles.',
                        'Cliquez sur "S\'inscrire" pour le repas souhaite.',
                        'Indiquez le nombre d\'adultes et d\'enfants.',
                        'Validez votre inscription.',
                    ],
                    'tips' => [
                        'Les inscriptions se ferment automatiquement le samedi.',
                        'Vous pouvez modifier ou annuler votre inscription tant que le repas est ouvert.',
                        'Le nombre de places peut etre limite par l\'administrateur.',
                    ],
                ],
                [
                    'key'         => 'reparations-achats',
                    'title'       => 'Reparations & Achats',
                    'icon'        => 'ki-questionnaire-tablet',
                    'icon_paths'  => 2,
                    'roles'       => [],
                    'description' => 'Soumettez une demande de reparation ou d\'achat pour la communaute. Le formulaire se deroule en deux etapes selon le type de demande choisi.',
                    'steps' => [
                        'Cliquez sur "Reparations & Achats" dans le menu lateral.',
                        'Etape 1 : Choisissez le type de demande (reparation ou achat).',
                        'Etape 2 : Remplissez les details specifiques selon le type choisi.',
                        'Pour une reparation : decrivez le probleme, selectionnez le lieu et l\'urgence.',
                        'Pour un achat : decrivez l\'article, indiquez le prix estime et la justification.',
                        'Validez votre demande. Un QR code de suivi est genere automatiquement.',
                    ],
                    'tips' => [
                        'Vous pouvez telecharger le QR code pour suivre votre demande.',
                        'Les demandes urgentes sont signalees a l\'administrateur.',
                        'Soyez precis dans la description pour faciliter le traitement.',
                    ],
                ],
                [
                    'key'         => 'mes-demandes',
                    'title'       => 'Mes Demandes',
                    'icon'        => 'ki-clipboard',
                    'icon_paths'  => 3,
                    'roles'       => [],
                    'description' => 'Suivez l\'etat de toutes vos demandes de reparation et d\'achat. Vous pouvez voir le statut de traitement et les details de chaque demande.',
                    'steps' => [
                        'Cliquez sur "Mes Demandes" dans le menu lateral.',
                        'Consultez la liste de vos demandes avec leur statut (en attente, en cours, terminee).',
                        'Cliquez sur une demande pour voir ses details complets.',
                    ],
                    'tips' => [
                        'Les administrateurs voient toutes les demandes de la communaute.',
                        'Filtrez vos demandes par type (reparation/achat) ou par statut.',
                    ],
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Communication
        |----------------------------------------------------------------------
        */
        'communication' => [
            'title' => 'Communication',
            'icon'  => 'ki-message-text-2',
            'icon_paths' => 3,
            'entries' => [
                [
                    'key'         => 'gestion-bulletin',
                    'title'       => 'Gestion Bulletin',
                    'icon'        => 'ki-document',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN, PlatformRole::COMMUNICATION],
                    'description' => 'Creez et gerez les bulletins hebdomadaires. Ajoutez les services liturgiques, les activites de la semaine et les reflexions spirituelles pour chaque edition.',
                    'steps' => [
                        'Cliquez sur "Gestion Bulletin" dans la section Communication du menu.',
                        'La liste de tous les bulletins s\'affiche avec leur statut de publication.',
                        'Cliquez sur "Creer un bulletin" pour une nouvelle edition.',
                        'Renseignez les dates de la semaine et le contenu.',
                        'Ajoutez des services, activites et reflexions via les formulaires dedies.',
                        'Publiez le bulletin lorsqu\'il est pret en cliquant sur "Publier".',
                    ],
                    'tips' => [
                        'Le numero d\'edition s\'incremente automatiquement chaque annee.',
                        'Un bulletin non publie n\'est pas visible par les membres.',
                        'Vous pouvez depublier un bulletin a tout moment.',
                    ],
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Administration
        |----------------------------------------------------------------------
        */
        'administration' => [
            'title' => 'Administration',
            'icon'  => 'ki-setting-3',
            'icon_paths' => 5,
            'entries' => [
                [
                    'key'         => 'gestion-vehicules',
                    'title'       => 'Gestion Vehicules',
                    'icon'        => 'ki-setting-3',
                    'icon_paths'  => 5,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Ajoutez, modifiez et gerez les vehicules de la communaute. Activez ou desactivez la disponibilite de chaque vehicule.',
                    'steps' => [
                        'Cliquez sur "Gestion Vehicules" dans la section Administration.',
                        'La liste des vehicules s\'affiche avec leur statut.',
                        'Cliquez sur "Ajouter un vehicule" pour en creer un nouveau.',
                        'Renseignez les informations (nom, type, immatriculation, etc.).',
                        'Utilisez le bouton de bascule pour activer/desactiver un vehicule.',
                    ],
                    'tips' => [
                        'Un vehicule desactive n\'apparait plus dans le planning.',
                        'Les reservations existantes ne sont pas annulees lors de la desactivation.',
                    ],
                ],
                [
                    'key'         => 'gestion-menu',
                    'title'       => 'Gestion Menu',
                    'icon'        => 'ki-coffee',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Gerez les menus de la semaine. Definissez les plats pour chaque jour (midi et soir) et activez ou desactivez les menus.',
                    'steps' => [
                        'Cliquez sur "Gestion Menu" dans la section Administration.',
                        'La page d\'edition affiche tous les jours de la semaine.',
                        'Modifiez le contenu de chaque menu (midi/soir) directement.',
                        'Utilisez le bouton de bascule pour activer/desactiver un menu.',
                        'Les modifications sont sauvegardees individuellement.',
                    ],
                    'tips' => [
                        'Un menu desactive n\'est pas visible par les membres.',
                        'Les menus sont organises par jour et par periode (midi/soir).',
                    ],
                ],
                [
                    'key'         => 'gestion-repas',
                    'title'       => 'Gestion Repas',
                    'icon'        => 'ki-gift',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Creez et gerez les repas du dimanche. Suivez les inscriptions, ouvrez ou fermez les inscriptions, et consultez les statistiques.',
                    'steps' => [
                        'Cliquez sur "Gestion Repas" dans la section Administration.',
                        'La liste des repas s\'affiche avec le nombre d\'inscrits.',
                        'Cliquez sur "Creer un repas" pour planifier un nouveau dimanche.',
                        'Renseignez la date, le menu et le nombre de places maximum.',
                        'Ouvrez les inscriptions quand vous etes pret.',
                        'Consultez la liste des inscrits en cliquant sur un repas.',
                    ],
                    'tips' => [
                        'Les repas se generent automatiquement 4 semaines a l\'avance.',
                        'Les inscriptions se ferment automatiquement le samedi.',
                        'Vous pouvez forcer la fermeture ou la reouverture a tout moment.',
                    ],
                ],
                [
                    'key'         => 'gestion-demandes',
                    'title'       => 'Gestion Demandes',
                    'icon'        => 'ki-questionnaire-tablet',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Consultez et traitez toutes les demandes de reparation et d\'achat de la communaute. Filtrez par type, statut ou urgence.',
                    'steps' => [
                        'Cliquez sur "Gestion Demandes" dans la section Administration.',
                        'La liste de toutes les demandes s\'affiche.',
                        'Utilisez les filtres pour trier par type ou statut.',
                        'Cliquez sur une demande pour la traiter.',
                        'Mettez a jour le statut (en cours, terminee, refusee).',
                        'Consultez les statistiques globales via le bouton dedie.',
                    ],
                    'tips' => [
                        'Les demandes urgentes apparaissent en priorite.',
                        'Vous pouvez exporter la liste des demandes.',
                    ],
                ],
                [
                    'key'         => 'membres',
                    'title'       => 'Membres',
                    'icon'        => 'ki-profile-user',
                    'icon_paths'  => 4,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Gerez les membres de la communaute. Ajoutez de nouveaux membres, modifiez les informations et les roles, activez ou desactivez des comptes.',
                    'steps' => [
                        'Cliquez sur "Membres" dans la section Administration.',
                        'La liste de tous les membres s\'affiche.',
                        'Cliquez sur "Ajouter un membre" pour creer un nouveau compte.',
                        'Renseignez les informations personnelles et le(s) role(s).',
                        'Utilisez le bouton de bascule pour activer/desactiver un compte.',
                        'Cliquez sur un membre pour modifier ses informations.',
                    ],
                    'tips' => [
                        'Un compte desactive ne peut plus se connecter.',
                        'Les roles disponibles sont : Administrateur, Communication, Membre, Autre.',
                        'Preferez desactiver un compte plutot que le supprimer pour conserver l\'historique.',
                    ],
                ],
                [
                    'key'         => 'demandes-adhesion',
                    'title'       => 'Demandes d\'adhesion',
                    'icon'        => 'ki-profile-user',
                    'icon_paths'  => 4,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Examinez et traitez les demandes de creation de compte soumises via le formulaire public. Approuvez ou refusez chaque demande.',
                    'steps' => [
                        'Cliquez sur "Demandes d\'adhesion" dans la section Administration.',
                        'Un badge indique le nombre de demandes en attente.',
                        'Cliquez sur une demande pour voir les details du demandeur.',
                        'Approuvez la demande pour creer automatiquement le compte.',
                        'Ou refusez la demande si elle ne correspond pas aux criteres.',
                    ],
                    'tips' => [
                        'L\'approbation cree automatiquement un compte et envoie un email au demandeur.',
                        'Les demandes refusees sont conservees dans l\'historique.',
                    ],
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Configuration
        |----------------------------------------------------------------------
        */
        'configuration' => [
            'title' => 'Configuration',
            'icon'  => 'ki-setting-2',
            'icon_paths' => 2,
            'entries' => [
                [
                    'key'         => 'communautes',
                    'title'       => 'Communautes',
                    'icon'        => 'ki-people',
                    'icon_paths'  => 5,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Gerez la liste des communautes. Ajoutez, modifiez ou supprimez des communautes qui servent de reference dans l\'application.',
                    'steps' => [
                        'Cliquez sur "Communautes" dans la section Configuration.',
                        'La liste des communautes existantes s\'affiche.',
                        'Cliquez sur "Ajouter" pour creer une nouvelle communaute.',
                        'Renseignez le nom et validez.',
                        'Utilisez les boutons d\'edition ou de suppression pour gerer les communautes.',
                    ],
                    'tips' => [
                        'La suppression d\'une communaute peut impacter les membres qui y sont rattaches.',
                    ],
                ],
                [
                    'key'         => 'acces-vehicule',
                    'title'       => 'Acces Vehicule',
                    'icon'        => 'ki-car',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Configurez les types de vehicules auxquels chaque membre a acces. Cela determine quels vehicules un membre peut reserver.',
                    'steps' => [
                        'Cliquez sur "Acces Vehicule" dans la section Configuration.',
                        'La matrice des acces s\'affiche (membres x types de vehicules).',
                        'Cochez ou decochez les cases pour accorder ou retirer les acces.',
                        'Les modifications sont prises en compte immediatement.',
                    ],
                    'tips' => [
                        'Un membre sans aucun acces vehicule ne pourra pas creer de reservation.',
                        'Par defaut, les nouveaux membres n\'ont acces a aucun type de vehicule.',
                    ],
                ],
                [
                    'key'         => 'lieux-reparation',
                    'title'       => 'Lieux Reparation',
                    'icon'        => 'ki-geolocation',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Gerez les lieux de reparation disponibles dans les demandes de reparation. Ajoutez, modifiez ou supprimez des lieux.',
                    'steps' => [
                        'Cliquez sur "Lieux Reparation" dans la section Configuration.',
                        'La liste des lieux existants s\'affiche.',
                        'Cliquez sur "Ajouter" pour creer un nouveau lieu.',
                        'Renseignez le nom et l\'adresse du lieu.',
                        'Utilisez les boutons d\'edition ou de suppression pour gerer les lieux.',
                    ],
                    'tips' => [
                        'Ces lieux apparaissent dans le formulaire de demande de reparation.',
                    ],
                ],
                [
                    'key'         => 'parametres-vehicules',
                    'title'       => 'Parametres Vehicules',
                    'icon'        => 'ki-time',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Configurez les parametres generaux des reservations de vehicules : horaires autorisees, duree maximale, etc.',
                    'steps' => [
                        'Cliquez sur "Parametres Vehicules" dans la section Configuration.',
                        'Modifiez les parametres selon vos besoins.',
                        'Cliquez sur "Enregistrer" pour sauvegarder.',
                    ],
                    'tips' => [
                        'Ces parametres s\'appliquent a toutes les nouvelles reservations.',
                        'Les reservations existantes ne sont pas affectees par les changements.',
                    ],
                ],
                [
                    'key'         => 'emails-echoues',
                    'title'       => 'Emails echoues',
                    'icon'        => 'ki-sms',
                    'icon_paths'  => 2,
                    'roles'       => [PlatformRole::ADMIN],
                    'description' => 'Consultez les emails qui n\'ont pas pu etre envoyes. Marquez-les comme resolus ou supprimez-les apres investigation.',
                    'steps' => [
                        'Cliquez sur "Emails echoues" dans la section Configuration.',
                        'Un badge indique le nombre d\'emails non resolus.',
                        'Consultez les details de chaque echec (destinataire, erreur, date).',
                        'Marquez un email comme resolu apres investigation.',
                        'Supprimez les entrees qui ne sont plus pertinentes.',
                    ],
                    'tips' => [
                        'Les echecs d\'envoi n\'empechent jamais les actions utilisateur.',
                        'Verifiez regulierement cette page pour detecter des problemes de configuration email.',
                    ],
                ],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Mon Compte
        |----------------------------------------------------------------------
        */
        'profil' => [
            'title' => 'Mon Compte',
            'icon'  => 'ki-user',
            'icon_paths' => 2,
            'entries' => [
                [
                    'key'         => 'mon-profil',
                    'title'       => 'Mon Profil',
                    'icon'        => 'ki-user',
                    'icon_paths'  => 2,
                    'roles'       => [],
                    'description' => 'Consultez et modifiez vos informations personnelles. Changez votre mot de passe et mettez a jour vos coordonnees.',
                    'steps' => [
                        'Cliquez sur "Mon Profil" en bas du menu lateral.',
                        'Consultez vos informations actuelles.',
                        'Cliquez sur "Modifier" pour mettre a jour vos informations.',
                        'Modifiez les champs souhaites (nom, email, telephone, etc.).',
                        'Pour changer de mot de passe, utilisez la section dediee.',
                        'Cliquez sur "Enregistrer" pour sauvegarder vos modifications.',
                    ],
                    'tips' => [
                        'Votre email est utilise pour les notifications et la recuperation de mot de passe.',
                        'Le mot de passe doit contenir au moins 8 caracteres.',
                    ],
                ],
            ],
        ],

    ],

];
