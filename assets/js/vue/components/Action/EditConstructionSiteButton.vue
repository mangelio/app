<template>
  <button-with-modal-confirm
      :button-disabled="patching" :title="$t('_action.edit_construction_site.title')"
      :confirm-title="$t('_action.save_changes')" :can-confirm="canConfirm"
      @confirm="confirm">
    <template v-slot:button-content>
      <font-awesome-icon :icon="['fal', 'pencil']" />
    </template>

    <construction-site-form :template="constructionSite" @update="patch = $event" />
    <image-form @update="image = $event" />
  </button-with-modal-confirm>
</template>

<script>

import { api } from '../../services/api'
import ButtonWithModalConfirm from '../Library/Behaviour/ButtonWithModalConfirm'
import ConstructionSiteForm from '../Form/ConstructionSiteForm'
import ImageForm from '../Form/ImageForm'

export default {
  components: {
    ImageForm,
    ConstructionSiteForm,
    ButtonWithModalConfirm,
  },
  data () {
    return {
      image: null,
      patch: null,
      patching: false
    }
  },
  props: {
    constructionSite: {
      type: Object,
      required: true
    }
  },
  computed: {
    canConfirm: function () {
      return this.pendingChanges > 0
    },
    pendingChanges: function () {
      let count = this.pendingPatch ? 1 : 0
      count += this.image ? 1 : 0

      return count
    },
    pendingPatch: function () {
      return this.patch && Object.keys(this.patch).length
    }
  },
  methods: {
    confirm: function () {
      this.patching = true

      if (this.pendingPatch) {
        api.patch(this.constructionSite, this.patch, this.$t('_action.edit_construction_site.saved'))
            .then(_ => {
              this.patch = null
              this.patching = this.pendingChanges > 0
            })
      }
      if (this.image) {
        api.postConstructionSiteImage(this.constructionSite, this.image, this.$t('_action.edit_construction_site.replaced_construction_site_image'))
            .then(_ => {
              this.image = null
              this.patching = this.pendingChanges > 0
            })
      }
    }
  }
}
</script>
