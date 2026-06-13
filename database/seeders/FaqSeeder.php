<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'question' => 'Dove trovo le etichette di spedizione dopo l\'acquisto?',
                'answer' => 'Le etichette non vengono inviate via email. Una volta che l\'ordine è stato completato e approvato, le troverai disponibili per il download e la stampa esclusivamente all\'interno della tua Area Cliente sul portale.',
            ],
            [
                'question' => 'Posso scrivere l\'indirizzo a mano sul pacco?',
                'answer' => 'Assolutamente no. È obbligatorio utilizzare l\'etichetta PDF generata dal sistema. L\'uso di etichette manoscritte o diverse da quelle fornite comporta il rischio di ritiro da parte del corriere e solleva la Società da ogni responsabilità per smarrimento o ritardo.',
            ],
            [
                'question' => 'Cosa succede se il peso o le misure del pacco sono superiori a quanto dichiarato?',
                'answer' => 'In caso di discrepanze, la merce può essere restituita al mittente o trattenuta in filiale fino al saldo della differenza. Se il corriere inoltra comunque il pacco, riceverai una richiesta di pagamento per la differenza tariffaria. Verrà applicata una penale di 5,00 euro per la gestione amministrativa.',
            ],
            [
                'question' => 'Come devo applicare correttamente l\'etichetta?',
                'answer' => 'L\'etichetta deve essere stampata in modo leggibile e affissa su una superficie piana del pacco. Assicurati che i codici a barre non siano coperti da nastro adesivo scuro, pieghe o imballaggi riflettenti. Ricorda di rimuovere ogni vecchia etichetta.',
            ],
            [
                'question' => 'Cosa accade se il destinatario non è presente alla consegna?',
                'answer' => 'La spedizione verrà messa in Giacenza presso il magazzino del Vettore. I costi di giacenza, riconsegna o l\'eventuale ritorno al mittente (in caso di mancato svincolo o rifiuto della merce) sono interamente a carico del Cliente.',
            ],
            [
                'question' => 'Esistono limiti su cosa posso spedire?',
                'answer' => 'Sì. Esistono merci proibite per legge o dai regolamenti dei Vettori (es. materiali infiammabili, preziosi, ecc.). Ti invitiamo a consultare la sezione «Articoli Proibiti» nella nostra Centrale Assistenza.',
            ],
            [
                'question' => 'Come posso monitorare la mia spedizione?',
                'answer' => 'Attraverso il Numero di Tracking associato al tuo ordine. Puoi inserire questo codice nella sezione dedicata del portale per visualizzare in tempo reale tutte le fasi: dal ritiro (Pick-up) fino alla consegna finale.',
            ],
            [
                'question' => 'Posso modificare un ordine già confermato e pagato?',
                'answer' => 'No. Qualsiasi modifica apportata dopo la conferma (es. cambio indirizzo o dimensioni) è considerata un nuovo ordine. Verifica sempre con attenzione la veridicità dei dati inseriti prima di concludere l\'acquisto.',
            ],
            [
                'question' => 'Come si richiede l\'annullamento di una spedizione?',
                'answer' => 'La richiesta deve essere inoltrata esclusivamente tramite il modulo «Annullamento e Rimborso» presente nella nostra Centrale di Assistenza. Una volta inviata, la richiesta è irrevocabile.',
            ],
            [
                'question' => 'Quali sono i tempi e le modalità di rimborso?',
                'answer' => 'Il rimborso viene emesso entro 15 giorni naturali e consecutivi dalla richiesta, utilizzando la stessa modalità di pagamento scelta in fase di acquisto.',
            ],
            [
                'question' => 'Chi risponde in caso di danni alla merce?',
                'answer' => 'La responsabilità dell\'imballaggio è del Cliente; deve essere idoneo a proteggere il prodotto da urti e cadute. In caso di danni imputabili al Vettore, il reclamo deve essere presentato tassativamente entro 8 giorni.',
            ],
        ];

        Faq::query()->delete();

        foreach ($rows as $i => $row) {
            Faq::query()->create([
                'question' => $row['question'],
                'answer' => $row['answer'],
                'sort_order' => $i + 1,
            ]);
        }
    }
}
