export default {
  de: {
    edit_maps: {
      help: 'Ändern Sie die Zuordnung oder die Bauplanversion.',
      default_map_name: 'Neuer Bauplan',
      actions: {
        add_map: 'Bauplan hinzufügen',
        add_map_files: 'Bauplanversionen hinzufügen',
        hide_map_files: 'Bauplanversionen verstecken',
        save_changes: '{pendingChangesCount} Änderungen speichern'
      }
    },
    edit_map_files: {
      help: 'Ordnen Sie die Bauplanversionen einem Bauplan zu.',
      drag_files_to_upload: 'Ziehen Sie .pdf Dateien in diesen Bereich um diese als neue Bauplanversionen hinzuzufügen',
      performing_upload_check: 'Upload wird geprüft...',
      identical_content_than: 'Diese Bauplanversion ist identitisch zu {files}.',
      identical_name: 'Eine Bauplanversion mit diesem Namen existiert bereits. Die Bauplanversion wird zu {new_name} umbenannt.',
      upload_active: 'wird hochgeladen... ({percentage}%)',
      actions: {
        abort_upload: 'Hochladen abbrechen',
        confirm_upload: 'Fortfahren'
      }
    },
    edit_craftsmen: {
      help: 'Aktualisieren Sie die Daten der Handwerker.',
      actions: {
        add_craftsman: 'Handwerker hinzufügen',
        save_changes: '{pendingChangesCount} Änderungen speichern',
        show_import: 'Aus Excel importieren',
        hide_import: "Import verstecken"
      },
      defaults: {
        contact_name: '',
        email: '',
        company: '',
        trade: ''
      }
    },
    import_craftsmen: {
      title: 'Importieren',
      help: 'Importieren Sie die Handwerker aus einem Excel',
      copy_paste_from_excel: 'Markieren Sie alle gewünschten Zellen (inklusive Überschriften) im Excel und drücken Sie Ctrl-C zum kopieren. Fügen Sie die Auswahl dann in das Textfeld unten mit Ctrl-V ein.',
      copy_paste_area_placeholder: 'Hier Excel Felder hineinkopieren',
      content_types_valid: 'Alle benötigten Felder wurden zugeordnet.',
      invalid_content_types: 'Bitte korrigieren Sie die Zuordnungen für {invalidContentTypes}. Jeder Typ muss genau einmal zugeordnet werden.',
      change_preview: 'Änderungsvorschau',
      changes_detected: 'Es wurden Änderungen entdeckt. Bestätigen Sie diese mit einem Klick auf "Ausführen".',
      no_more_changes_detected: 'Es wurden keine weiteren Änderungen entdeckt. Sie können den Import nun schliessen.',
      content_types: {
        email: 'E-Mail',
        company: 'Firma',
        contact_name: 'Kontaktperson',
        trade: 'Gewerbe / Arbeitsgattung',
      },
      changes: {
        title: 'Änderung',
        add: 'wird erstellt',
        update: 'wird aktualisiert',
        remove: 'wird entfernt',
      },
      actions: {
        apply_import: "Ausführen",
        abort: "Abbrechen",
        close: 'Schliessen'
      }
    },
    set_automatically: 'automatisch festlegen',
    issue_count: 'Anzahl Pendenzen'
  },
  it: {
    actions: {
      switch: 'Mostrare'
    }
  }
};
