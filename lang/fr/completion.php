<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'completion', language 'fr', branch 'MOODLE_22_STABLE'
 *
 * @package   completion
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['achievinggrade'] = 'Obtenir la note';
$string['activities'] = 'Activités';
$string['activitiescompleted'] = 'Activités terminées';
$string['activitycompletion'] = 'Achèvement d\'activité';
$string['activityrpl'] = 'VAE d\'activité';
$string['addcourseprerequisite'] = 'Ajouter un cours prérequis';
$string['afterspecifieddate'] = 'Après la date indiquée';
$string['aggregateall'] = 'Tout';
$string['aggregateany'] = 'Au moins un';
$string['aggregationmethod'] = 'Méthode de combinaison';
$string['all'] = 'Tous';
$string['any'] = 'Un au moins';
$string['approval'] = 'Approbation';
$string['badautocompletion'] = 'Lorsque vous utilisez le suivi automatique de l\'achèvement, vous devez activer au moins une exigence (ci-dessous).';
$string['complete'] = 'A achèver';
$string['completed'] = 'Terminé';
$string['completedunlocked'] = 'Options d\'achèvement déverrouillées';
$string['completedunlockedtext'] = 'Lors de l\'enregistrement, l\'état d\'achèvement des activités de tous les participants sera supprimé. Si ce n\'est pas ce que vous voulez, n\'enregistrez pas.';
$string['completedwarning'] = 'Options d\'achèvement verrouillées';
$string['completedwarningtext'] = 'Un ou plusieurs participants ({$a}) ont déjà marqué cette activité comme terminée. La modification des options d\'achèvement supprimera cet état terminé et risque de causer des confusions. Les options d\'achèvement ont été verrouillées et il n\'est pas recommandé de les déverrouiller à moins que cela ne soit absolument nécessaire.';
$string['completeviarpl'] = 'Compléter via rpl';
$string['completion'] = 'Suivi d\'achèvement';
$string['completion-alt-auto-enabled'] = 'Le système marquera cet élément comme terminé selon les conditions : {$a}';
$string['completion-alt-auto-fail'] = 'Terminé : {$a} (n\'a pas atteint la note pour passer)';
$string['completion-alt-auto-n'] = 'Non terminé : {$a}';
$string['completion-alt-auto-pass'] = 'Terminé : {$a} (a atteint la note pour passer)';
$string['completion-alt-auto-y'] = 'Terminé : {$a}';
$string['completion-alt-manual-enabled'] = 'Les participants peuvent marquer manuellement cet élément comme terminé : {$a}';
$string['completion-alt-manual-n'] = 'Non terminé : {$a}. Sélectionner pour marquer comme terminé.';
$string['completion-alt-manual-y'] = 'Terminé : {$a}. Sélectionner pour marquer comme non terminé.';
$string['completion-fail'] = 'Terminé (n\'a pas atteint la note pour passer)';
$string['completion-n'] = 'Pas terminé';
$string['completion-pass'] = 'Terminé (note pour passer atteinte)';
$string['completion-title-manual-n'] = 'Marquer comme terminé : {$a}';
$string['completion-title-manual-y'] = 'Marquer comme non terminé : {$a}';
$string['completion-y'] = 'Terminé';
$string['completion_automatic'] = 'Afficher l\'activité comme terminée dès que les conditions sont remplies';
$string['completion_help'] = 'Cette option permet d\'activer le suivi de l\'achèvement des activités, manuellement ou automatiquement, sur la base de conditions choisies. Plusieurs conditions peuvent être exigées simultanément. Dans ce cas, l\'activité sera considérée comme terminée si toutes les conditions sont requises.
Une coche à côté du nom de l\'activité indique sur la page de cours lorsqu\'une activité est terminée.';
$string['completion_manual'] = 'Les participants peuvent marquer manuellement cette activité comme terminée';
$string['completion_none'] = 'Ne pas afficher l\'état d\'achèvement';
$string['completiondisabled'] = 'Désactivé. Pas affiché dans les réglages de l\'activité';
$string['completionenabled'] = 'Activé. La configuration s\'effectue dans les réglages des activités';
$string['completionexpected'] = 'Achèvement attendu le';
$string['completionexpected_help'] = 'Cette option vous permet d\'indiquer une date à laquelle l\'activité devrait être terminée. Cette date n\'est pas visible pour les participants et n\'est affichée que dans le rapport de l\'état d\'achèvement.';
$string['completionicons'] = 'Coches d\'achèvement';
$string['completionicons_help'] = 'Une coche à côté du nom de l\'activité peut être utilisée pour indiquer sur la page de cours lorsqu\'une activité est terminée.
Si la coche est traitillée, vous pouvez la cliquer lorsque vous pensez avoir terminé l\'activité. Vous pouvez cliquer une nouvelle fois si vous changez d\'avis. La coche est optionnelle et vous permet simplement de savoir où vous en êtes dans le cours.
Si une case blanche est affichée, une coche apparaîtra automatiquement lorsque l\'activité sera terminée d\'après les conditions fixées par l\'enseignant.';
$string['completionmenuitem'] = 'Achèvement';
$string['completionnotenabled'] = 'L\'achèvement n\'est pas activé';
$string['completionnotenabledforcourse'] = 'L\'achèvement n\'est pas activé pour ce cours';
$string['completionnotenabledforsite'] = 'L\'achèvement n\'est pas activé pour ce site';
$string['completiononunenrolment'] = 'Achèvement lors de la désinscription';
$string['completionsettingslocked'] = 'Réglages d\'achèvement verrouillés';
$string['completionstartonenrol'] = 'Le suivi de l\'achèvement commence à l\'inscription';
$string['completionstartonenrolhelp'] = 'Commencer le suivi de l\'achèvement des activités des étudiants dès l\'inscription au cours';
$string['completionusegrade'] = 'Note requise';
$string['completionusegrade_desc'] = 'Les étudiants doivent recevoir une note pour terminer cette activité';
$string['completionusegrade_help'] = 'Quand cette option est activée, les étudiants doivent obtenir une note à l\'activité pour la terminer. Des icônes de réussite ou d\'échec peuvent être affichées, si une note minimale pour réussir a été spécifiée.';
$string['completionview'] = 'Affichage requis';
$string['completionview_desc'] = 'Les étudiants doivent afficher cette activité pour la terminer';
$string['configenablecompletion'] = 'L\'activation de ce réglage permet le suivi de l\'achèvement des activités au niveau des cours.';
$string['configenablecourserpl'] = 'Quand activé, un cours peut être marqué comme terminé en assignant à l\'utilisateur un enregistrement d\'apprentissage préalable.';
$string['configenablemodulerpl'] = 'Quand activé pour un module, tout critère de cours terminé pour ce type de module peut être marqué comme terminé en assignant à l\'utilisateur un enregistrement d\'apprentissage préalable.';
$string['confirmselfcompletion'] = 'Confirmer l\'auto achèvement';
$string['coursealreadycompleted'] = 'Vous avez déjà terminé ce cours';
$string['coursecomplete'] = 'Cours terminé';
$string['coursecompleted'] = 'Cours terminé';
$string['coursegrade'] = 'Note du cours';
$string['courseprerequisites'] = 'Cours prérequis';
$string['courserpl'] = 'VAE de cours';
$string['courserplorallcriteriagroups'] = 'VAE pour le cours ou <br />tout les groupes de critères';
$string['courserploranycriteriagroup'] = 'VAE pour le cours ou <br />un des groupes de critères';
$string['coursesavailable'] = 'Cours disponibles';
$string['coursesavailableexplaination'] = '<i>Des critères d\'achèvement de cours doivent être fixés pour qu\'un cours apparaisse dans cette liste</i>';
$string['criteria'] = 'Critères';
$string['criteriagradenote'] = 'La modification de la note requise ne modifiera pas la note pour réussir le cours définie actuellement.';
$string['criteriagroup'] = 'Groupe de critères';
$string['criteriarequiredall'] = 'Tous les critères ci-dessous sont requis';
$string['criteriarequiredany'] = 'Un des critères ci-dessous est requis';
$string['csvdownload'] = 'Télécharger en format CSV (UTF-8)';
$string['datepassed'] = 'Date échue';
$string['days'] = 'Jours';
$string['daysafterenrolment'] = 'Jours après l\'inscription';
$string['deletecoursecompletiondata'] = 'Supprimer les données d\'achèvement de cours';
$string['deletedcourse'] = 'Cours supprimé';
$string['dependencies'] = 'Dépendances';
$string['dependenciescompleted'] = 'Dépendances achèvées';
$string['durationafterenrolment'] = 'Durée après l\'inscription';
$string['editcoursecompletionsettings'] = 'Modifier les réglages d\'achèvement du cours';
$string['enablecompletion'] = 'Activer le suivi de l\'achèvement des activités';
$string['enablecourserpl'] = 'Activer le RPL pour les cours';
$string['enablemodulerpl'] = 'Activer le RPL pour les modules';
$string['enrolmentduration'] = 'Jours restants';
$string['err_noactivities'] = 'Le suivi de l\'achèvement n\'est activé dans aucune activité, et rien ne peut donc être affiché. Vous pouvez activer le suivi de l\'achèvement en modifiant les réglages des activités.';
$string['err_nocourses'] = 'Le suivi de l\'achèvement n\'est activé dans aucun cours. Vous pouvez activer le suivi d\'achèvement de cours dans les réglages de chaque cours.';
$string['err_nocriteria'] = 'Aucun critère d\'achèvement a été définie dans ce cours';
$string['err_nograde'] = 'Une note minimale pour terminer le cours avec succès n\'a pas été spécifiée. Pour activer ce type de critère, veuillez créer une note minimale pour ce cours.';
$string['err_noroles'] = 'Il n\'y a pas de rôle avec la capacité « moodle/course:markcomplete » dans ce cours. Pour activer ce type de critère, veuillez ajouter cette capacité à un ou des rôles.';
$string['err_nousers'] = 'Aucun des participants de ce cours ou de ce groupe n\'a de rôle pour lequel le suivi de l\'achèvement des activités est activé. Par défaut, le suivi de l\'achèvement des activités est activé pour le rôle d\'étudiant uniquement et vous obtiendrez cette erreur s\'il n\'y a pas d\'étudiant. L\'administrateur peut modifier ceci au besoin.';
$string['err_settingslocked'] = 'Un ou plusieurs étudiants ont déjà rempli un critère, c\'est pourquoi les réglages ont été verrouillés. Le déverrouillage des critères d\'achèvement supprimera les données existantes de l\'état d\'achèvement des utilisateurs et causera des confusions.';
$string['err_system'] = 'Une erreur interne est survenue dans le système de suivi de l\'achèvement des activités. L\'administrateur peut activer l\'affichage des informations de débogage pour obtenir plus de détails sur cette erreur.';
$string['error:rplsaredisabled'] = 'L\'enregistrement d\'apprentissage préalable a été désactivé par un administrateur.';
$string['excelcsvdownload'] = 'Télécharger en format CSV compatible Excel';
$string['fraction'] = 'Fraction';
$string['graderequired'] = 'Note requise';
$string['gradexrequired'] = '{$a} requis';
$string['inprogress'] = 'En cours';
$string['manualcompletionby'] = 'Achèvement manuel par';
$string['manualselfcompletion'] = 'Auto-achèvement manuel';
$string['markcomplete'] = 'Marquer comme terminé';
$string['markedcompleteby'] = 'Marqué comme terminé par {$a}';
$string['markingyourselfcomplete'] = 'Marquer vous-même comme terminé';
$string['moredetails'] = 'Plus de détails';
$string['nocriteriaset'] = 'Aucun critère d\'achèvement défini pour ce cours';
$string['notcompleted'] = 'Pas terminé';
$string['notenroled'] = 'Vous n\'êtes pas inscrit à ce cours';
$string['notyetstarted'] = 'Pas encore commencé';
$string['overallcriteriaaggregation'] = 'Type de combinaison des critères globaux';
$string['pending'] = 'En suspens';
$string['periodpostenrolment'] = 'Durée après l\'inscription';
$string['prerequisites'] = 'Prérequis';
$string['prerequisitescompleted'] = 'Prérequis terminés';
$string['progress'] = 'Suivi des activités des participants';
$string['progress-title'] = '{$a->user}, {$a->activity} : {$a->state} {$a->date}';
$string['recognitionofpriorlearning'] = 'Reconnaissance d\'apprentissages antérieurs';
$string['remainingenroledfortime'] = 'Rester inscrit pendant une durée spécifiée';
$string['remainingenroleduntildate'] = 'Rester inscrit jusqu\'à une date spécifiée';
$string['reportpage'] = 'Affichage des utilisateurs {$a->from} à {$a->to} sur {$a->total}.';
$string['requiredcriteria'] = 'Critères requis';
$string['restoringcompletiondata'] = 'Écriture des données d\'achèvement';
$string['rpl'] = 'VAE';
$string['saved'] = 'Enregistré';
$string['seedetails'] = 'Voir les détails';
$string['self'] = 'Soi-même';
$string['selfcompletion'] = 'Auto achèvement';
$string['showinguser'] = 'Affichage de l\'utilisateur';
$string['showrpl'] = 'Afficher VAE';
$string['showrpls'] = 'Afficher les VAEs';
$string['unenrolingfromcourse'] = 'Désinscription du cours';
$string['unenrolment'] = 'Désinscription';
$string['unit'] = 'Unité';
$string['unlockcompletion'] = 'Déverrouiller les options de suivi d\'achèvement';
$string['unlockcompletiondelete'] = 'Déverrouiller les options de suivi d\'achèvement et effacer les données d\'achèvement de l\'utilisateur';
$string['usealternateselector'] = 'Utiliser un autre sélecteur de cours';
$string['usernotenroled'] = 'L\'utilisateur n\'est pas inscrit à ce cours';
$string['viewcoursereport'] = 'Consulter le rapport du cours';
$string['viewingactivity'] = 'Affichage de {$a}';
$string['writingcompletiondata'] = 'Écriture des données d\'achèvement';
$string['xdays'] = '{$a} jours';
$string['yourprogress'] = 'Votre progression';
