// Requirement Builder for Admin Grant Creation
let requirementSections = [];

function initializeRequirementBuilder() {
    requirementSections = [];
    renderRequirementBuilder();
}

function renderRequirementBuilder() {
    const container = document.getElementById('requirementBuilder');
    if (!container) return;
    
    if (requirementSections.length === 0) {
        container.innerHTML = `
            <div class="no-requirements" style="text-align: center; padding: 20px; color: #a89bb8; background: #f9f7fc; border-radius: 8px; margin-bottom: 15px;">
                No requirement sections added yet. Click "Add Requirement Section" to start building your form.
            </div>
        `;
    } else {
        container.innerHTML = requirementSections.map((section, sectionIndex) => `
            <div class="requirement-section" data-section-index="${sectionIndex}" style="background: #f9f7fc; border: 2px solid #e8d9f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <input type="text" 
                           class="section-title-input" 
                           value="${section.title}" 
                           placeholder="Section Title (e.g., Personal Information)"
                           onchange="updateSectionTitle(${sectionIndex}, this.value)"
                           style="flex: 1; padding: 10px; border: 2px solid #d4a5e8; border-radius: 6px; font-size: 16px; font-weight: 600; color: #5a3fa3; margin-right: 10px;">
                    <button type="button" class="btn-small btn-delete" onclick="removeRequirementSection(${sectionIndex})" style="background: #ff9a9a; color: white;">Remove Section</button>
                </div>
                
                <div class="section-fields" style="margin-bottom: 15px;">
                    ${section.fields.map((field, fieldIndex) => `
                        <div class="field-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center; background: white; padding: 12px; border-radius: 6px; border: 1px solid #e8d9f0;">
                            <div style="flex: 1;">
                                <input type="text" 
                                       class="field-label-input" 
                                       value="${field.label}" 
                                       placeholder="Field Label (e.g., Full Name)"
                                       onchange="updateFieldLabel(${sectionIndex}, ${fieldIndex}, this.value)"
                                       style="width: 100%; padding: 8px; border: 1px solid #e8d9f0; border-radius: 4px; margin-bottom: 5px;">
                                <select class="field-type-select" 
                                        onchange="updateFieldType(${sectionIndex}, ${fieldIndex}, this.value)"
                                        style="width: 100%; padding: 6px; border: 1px solid #e8d9f0; border-radius: 4px; font-size: 12px;">
                                    <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text Input</option>
                                    <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                                    <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                                    <option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Telephone</option>
                                    <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                                    <option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option>
                                    <option value="select" ${field.type === 'select' ? 'selected' : ''}>Dropdown</option>
                                    <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                                    <option value="radio" ${field.type === 'radio' ? 'selected' : ''}>Radio Button</option>
                                </select>
                                ${field.type === 'select' || field.type === 'radio' ? `
                                    <input type="text" 
                                           class="field-options-input" 
                                           value="${field.options ? field.options.join(', ') : ''}" 
                                           placeholder="Options (comma-separated)"
                                           onchange="updateFieldOptions(${sectionIndex}, ${fieldIndex}, this.value)"
                                           style="width: 100%; padding: 6px; border: 1px solid #e8d9f0; border-radius: 4px; margin-top: 5px; font-size: 11px;">
                                ` : ''}
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <label style="font-size: 11px; color: #7d6b8f;">
                                    <input type="checkbox" ${field.required ? 'checked' : ''} onchange="updateFieldRequired(${sectionIndex}, ${fieldIndex}, this.checked)"> Required
                                </label>
                                <button type="button" class="btn-small" onclick="removeField(${sectionIndex}, ${fieldIndex})" style="background: #ff9a9a; color: white; font-size: 11px; padding: 5px 10px;">Remove</button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <button type="button" class="btn-small" onclick="addField(${sectionIndex})" style="background: #a8c5e0; color: white; width: 100%;">
                    + Add Field to "${section.title || 'Section'}"
                </button>
            </div>
        `).join('');
    }
}

function addRequirementSection() {
    requirementSections.push({
        title: '',
        fields: []
    });
    renderRequirementBuilder();
}

function removeRequirementSection(sectionIndex) {
    if (confirm('Are you sure you want to remove this requirement section and all its fields?')) {
        requirementSections.splice(sectionIndex, 1);
        renderRequirementBuilder();
    }
}

function updateSectionTitle(sectionIndex, title) {
    if (requirementSections[sectionIndex]) {
        requirementSections[sectionIndex].title = title;
    }
}

function addField(sectionIndex) {
    if (requirementSections[sectionIndex]) {
        requirementSections[sectionIndex].fields.push({
            label: '',
            type: 'text',
            required: true,
            options: null
        });
        renderRequirementBuilder();
    }
}

function removeField(sectionIndex, fieldIndex) {
    if (requirementSections[sectionIndex] && requirementSections[sectionIndex].fields[fieldIndex]) {
        requirementSections[sectionIndex].fields.splice(fieldIndex, 1);
        renderRequirementBuilder();
    }
}

function updateFieldLabel(sectionIndex, fieldIndex, label) {
    if (requirementSections[sectionIndex] && requirementSections[sectionIndex].fields[fieldIndex]) {
        requirementSections[sectionIndex].fields[fieldIndex].label = label;
    }
}

function updateFieldType(sectionIndex, fieldIndex, type) {
    if (requirementSections[sectionIndex] && requirementSections[sectionIndex].fields[fieldIndex]) {
        requirementSections[sectionIndex].fields[fieldIndex].type = type;
        if (type !== 'select' && type !== 'radio') {
            requirementSections[sectionIndex].fields[fieldIndex].options = null;
        } else if (!requirementSections[sectionIndex].fields[fieldIndex].options) {
            requirementSections[sectionIndex].fields[fieldIndex].options = [];
        }
        renderRequirementBuilder();
    }
}

function updateFieldOptions(sectionIndex, fieldIndex, optionsString) {
    if (requirementSections[sectionIndex] && requirementSections[sectionIndex].fields[fieldIndex]) {
        requirementSections[sectionIndex].fields[fieldIndex].options = optionsString.split(',').map(opt => opt.trim()).filter(opt => opt);
        renderRequirementBuilder();
    }
}

function updateFieldRequired(sectionIndex, fieldIndex, required) {
    if (requirementSections[sectionIndex] && requirementSections[sectionIndex].fields[fieldIndex]) {
        requirementSections[sectionIndex].fields[fieldIndex].required = required;
    }
}

function getRequirementSections() {
    return requirementSections;
}

function loadRequirementSections(sections) {
    requirementSections = sections || [];
    renderRequirementBuilder();
}

// Make functions global
window.addRequirementSection = addRequirementSection;
window.removeRequirementSection = removeRequirementSection;
window.updateSectionTitle = updateSectionTitle;
window.addField = addField;
window.removeField = removeField;
window.updateFieldLabel = updateFieldLabel;
window.updateFieldType = updateFieldType;
window.updateFieldOptions = updateFieldOptions;
window.updateFieldRequired = updateFieldRequired;
window.getRequirementSections = getRequirementSections;
window.loadRequirementSections = loadRequirementSections;
window.initializeRequirementBuilder = initializeRequirementBuilder;
window.renderRequirementBuilder = renderRequirementBuilder;

