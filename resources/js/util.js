export const appMixin = {
  data() {
    return {
      csrf: document.head.querySelector('meta[name="csrf-token"]').content,
    }
  },
  methods: {
    getImageUrl(name) {
      return new URL(`../images/${name}`, import.meta.url).href
    },
  }
}
