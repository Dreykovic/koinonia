/**
 * Monitoring front : capture des erreurs JS / AJAX et helpers d'affichage des erreurs.
 * Importé en premier par app.js (et seul par error-reporting.js sur les pages invitées)
 * pour capter aussi les erreurs du reste du bundle. Voir docs/logging.md.
 */
import { installErrorCapture } from './capture';
import { errorMessage, showError, showNotice } from './errors';
import { reportClientError } from './reporter';

installErrorCapture();

// API commune aux scripts des vues (scripts classiques, sans import possible) :
//   .fail(xhr => AppErrors.show(AppErrors.message(xhr, 'Erreur lors de la réservation')))
window.AppErrors = {
    message: errorMessage,
    show: showError,
    notice: showNotice,
    report: reportClientError,
};
