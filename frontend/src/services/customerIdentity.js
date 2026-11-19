// Keep inferred identity fields in sync until the user explicitly edits them.
export function customerIdentitySync(form, inferName = value => value) {
  let automatic;
  const reset = (profile = {}) => {
    automatic = Object.fromEntries(['orgFullName', 'orgShortName', 'letterheadAddress'].map(field => [field, !profile[field]]));
  };
  reset();
  return {
    reset,
    edit(field) { automatic[field] = false; },
    nameChanged() {
      const name = form.value.name || '';
      if (automatic.orgFullName) form.value.orgFullName = inferName(name);
      if (automatic.orgShortName) form.value.orgShortName = name.length <= 15 ? name : '';
    },
    addressChanged() {
      if (automatic.letterheadAddress) form.value.letterheadAddress = form.value.address || '';
    },
  };
}
