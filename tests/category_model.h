// category_model.h - C++ implementation of Category model for testing
#ifndef CATEGORY_MODEL_H
#define CATEGORY_MODEL_H

#include <string>
#include <vector>
#include <chrono>
#include <stdexcept>
#include <regex>

class Category {
private:
    std::string id;
    std::string name;
    std::string slug;
    std::string description;
    bool active;
    std::string parentId;
    int depth;
    std::vector<std::string> path;
    std::chrono::system_clock::time_point createdAt;
    std::chrono::system_clock::time_point updatedAt;

public:
    Category(const std::string& name, const std::string& slug, 
             const std::string& description = "", bool active = true,
             const std::string& parentId = "", const std::string& id = "")
        : name(name), slug(slug), description(description), active(active),
          parentId(parentId), depth(0), id(id) {
        createdAt = std::chrono::system_clock::now();
        updatedAt = createdAt;
        validate();
    }

    void validate() const {
        // Name validation
        if (name.length() < 3) {
            throw std::invalid_argument("Category name must be at least 3 characters");
        }
        if (name.length() > 100) {
            throw std::invalid_argument("Category name cannot exceed 100 characters");
        }

        // Slug validation
        std::regex slug_pattern("^[a-z0-9-]+$");
        if (!std::regex_match(slug, slug_pattern)) {
            throw std::invalid_argument("Slug must contain only lowercase letters, numbers, and hyphens");
        }
        if (slug.length() < 2) {
            throw std::invalid_argument("Slug must be at least 2 characters");
        }
        if (slug.length() > 50) {
            throw std::invalid_argument("Slug cannot exceed 50 characters");
        }

        // Description validation
        if (description.length() > 500) {
            throw std::invalid_argument("Description cannot exceed 500 characters");
        }
    }

    // Getters
    std::string getId() const { return id; }
    std::string getName() const { return name; }
    std::string getSlug() const { return slug; }
    std::string getDescription() const { return description; }
    bool isActive() const { return active; }
    std::string getParentId() const { return parentId; }
    int getDepth() const { return depth; }
    std::vector<std::string> getPath() const { return path; }

    // Setters
    void setName(const std::string& newName) {
        name = newName;
        updatedAt = std::chrono::system_clock::now();
        validate();
    }

    void setSlug(const std::string& newSlug) {
        slug = newSlug;
        updatedAt = std::chrono::system_clock::now();
        validate();
    }

    void setDepth(int newDepth) { depth = newDepth; }
    void setPath(const std::vector<std::string>& newPath) { path = newPath; }

    bool isRoot() const {
        return parentId.empty();
    }

    std::string getFullPath() const {
        if (path.empty()) {
            return name;
        }
        std::string result;
        for (size_t i = 0; i < path.size(); ++i) {
            if (i > 0) result += " > ";
            result += path[i];
        }
        return result;
    }
};

#endif // CATEGORY_MODEL_H